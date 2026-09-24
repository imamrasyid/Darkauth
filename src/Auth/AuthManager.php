<?php

namespace Darkauth\Auth;

use Darkauth\Core\GuardInterface;
use Darkauth\Drivers\SessionGuard;
use Darkauth\Drivers\JWTGuard;
use Darkauth\Auth\DatabaseUserProvider;
use Darkauth\Core\UserProviderInterface;
use Darkauth\Events\Dispatcher;
use Darkauth\Audit\AuditLogger;
use Darkauth\MFA\MFAInterface;
use Darkauth\MFA\TOTPDriver;
use Darkauth\Captcha\CaptchaInterface;
use Darkauth\Captcha\ReCaptchaDriver;
use Darkauth\Security\RiskEngine;
use Darkauth\Security\SecurityProfile;
use Darkauth\Security\TrustedDevice;
use Darkauth\Security\DatabaseRateLimiter;
use Darkauth\Auth\RecoveryWorkflow;
use Darkauth\Support\SessionStorage;
use Darkauth\Support\JwtHelper;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class AuthManager
 * 
 * The main orchestrator for Darkauth library.
 */
class AuthManager
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $guards = [];

    /**
     * @var array
     */
    protected $resolvedInstances = [];

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var array
     */
    protected $customCreators = [];

    /**
     * AuthManager constructor.
     *
     * @param array $config
     * @param Dispatcher|null $events
     */
    public function __construct(array $config, Dispatcher $events = null)
    {
        $this->config = $config;
        $this->events = $events ?: new Dispatcher();

        $this->bootAuditing();
    }

    /**
     * Initialize auditing if configured.
     */
    protected function bootAuditing()
    {
        if (isset($this->config['audit']['callback'])) {
            $hmacKey = $this->config['audit']['hmac_key'] ?? '';
            $logger = new AuditLogger($this->config['audit']['callback'], $hmacKey);
            $logger->subscribe($this->events);
        }
    }

    /**
     * Get a guard instance by name.
     *
     * @param string|null $name
     * @return GuardInterface
     */
    public function guard(string $name = null): GuardInterface
    {
        $name = $name ?: $this->getDefaultGuard();

        if (!isset($this->guards[$name])) {
            $this->guards[$name] = $this->resolve($name);
        }

        return $this->guards[$name];
    }

    /**
     * Resolve the given guard.
     *
     * @param string $name
     * @return GuardInterface
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name): GuardInterface
    {
        $config = $this->getGuardConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return call_user_func($this->customCreators[$config['driver']], $this, $name, $config);
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($name, $config);
        }

        throw new InvalidArgumentException("Auth driver [{$config['driver']}] for guard [{$name}] is not supported.");
    }

    /**
     * Create a Session driver instance.
     *
     * @param string $name
     * @param array $config
     * @return SessionGuard
     */
    protected function createSessionDriver(string $name, array $config): SessionGuard
    {
        $storage = new SessionStorage();
        $provider = $this->getProvider($config['provider']);

        return new SessionGuard($name, $storage, $provider, $this->events);
    }

    /**
     * Create a JWT driver instance.
     *
     * @param string $name
     * @param array $config
     * @return JWTGuard
     */
    protected function createJwtDriver(string $name, array $config): JWTGuard
    {
        $jwtConfig = $this->config['jwt'] ?? [];
        $secret = $jwtConfig['secret'] ?? null;

        $insecureDefaults = ['change-me', 'your-secret-key-change-me', 'secret', ''];
        if ($secret === null || in_array($secret, $insecureDefaults, true)) {
            throw new RuntimeException(
                'JWT secret must be set to a secure random value. '
                . 'Do not use default/insecure keys in production.'
            );
        }

        $jwtHelper = new JwtHelper($secret, $jwtConfig['algo'] ?? 'HS256', $jwtConfig['issuer'] ?? null);

        $provider = $this->getProvider($config['provider']);

        return new JWTGuard($jwtHelper, $provider, $this->events);
    }

    /**
     * Get the user provider instance.
     *
     * @param string $providerName
     * @return UserProviderInterface
     * @throws InvalidArgumentException
     */
    protected function getProvider(string $providerName): UserProviderInterface
    {
        $config = $this->config['providers'][$providerName] ?? null;

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth provider [{$providerName}] is not defined.");
        }

        $driver = $config['driver'] ?? 'database';

        if ($driver === 'database') {
            $callback = $config['callback'] ?? function($table, $criteria) { return null; };
            return new DatabaseUserProvider($callback, $config['table'] ?? 'users');
        }

        throw new InvalidArgumentException("Auth user provider driver [{$driver}] is not supported.");
    }

    /**
     * Get the guard configuration.
     *
     * @param string $name
     * @return array|null
     */
    protected function getGuardConfig(string $name): ?array
    {
        return $this->config['guards'][$name] ?? null;
    }

    /**
     * Get the default guard name.
     *
     * @return string
     */
    public function getDefaultGuard(): string
    {
        return $this->config['defaults']['guard'] ?? 'web';
    }

    /**
     * Revoke all active sessions for a specific user.
     *
     * @param mixed $userId
     * @return void
     */
    public function revokeAllSessionsForUser($userId)
    {
        $trustedDevice = $this->getTrustedDeviceManager();
        $trustedDevice->revokeAll($userId);

        $this->events->dispatch('auth.sessions.revoked', ['user_id' => $userId]);
    }

    /**
     * Get the MFA service.
     *
     * @return MFAInterface
     */
    public function mfa(): MFAInterface
    {
        $key = 'mfa';
        if (!isset($this->resolvedInstances[$key])) {
            $storageCallback = $this->config['mfa']['callback'] ?? null;
            $this->resolvedInstances[$key] = new TOTPDriver($storageCallback);
        }
        return $this->resolvedInstances[$key];
    }

    /**
     * Get the Captcha service.
     *
     * @param string|null $name
     * @return CaptchaInterface
     */
    public function captcha(string $name = null): CaptchaInterface
    {
        $name = $name ?: ($this->config['captcha']['driver'] ?? 'recaptcha');
        $config = $this->config['captcha']['drivers'][$name] ?? [];

        if ($name === 'recaptcha') {
            if (empty($config['site_key']) || empty($config['secret_key'])) {
                throw new InvalidArgumentException("Captcha driver [{$name}] requires 'site_key' and 'secret_key' in configuration.");
            }
            return new ReCaptchaDriver($config['site_key'], $config['secret_key']);
        }

        throw new InvalidArgumentException("Captcha driver [{$name}] is not supported.");
    }

    /**
     * Get the Risk Engine.
     *
     * @return RiskEngine
     */
    public function getRiskEngine(): RiskEngine
    {
        $key = 'risk_engine';
        if (!isset($this->resolvedInstances[$key])) {
            $this->resolvedInstances[$key] = new RiskEngine();
        }
        return $this->resolvedInstances[$key];
    }

    /**
     * Get the Security Profile manager.
     *
     * @return SecurityProfile
     */
    public function getSecurityProfile(): SecurityProfile
    {
        $key = 'security_profile';
        if (!isset($this->resolvedInstances[$key])) {
            $this->resolvedInstances[$key] = new SecurityProfile();
        }
        return $this->resolvedInstances[$key];
    }

    /**
     * Get the Recovery Workflow manager.
     *
     * @return RecoveryWorkflow
     */
    public function getRecoveryWorkflow(): RecoveryWorkflow
    {
        $key = 'recovery_workflow';
        if (!isset($this->resolvedInstances[$key])) {
            $storageCallback = $this->config['recovery']['callback'] ?? function(){};
            $this->resolvedInstances[$key] = new RecoveryWorkflow($storageCallback, $this->events);
        }
        return $this->resolvedInstances[$key];
    }

    /**
     * Get the Trusted Device manager.
     *
     * @return TrustedDevice
     */
    public function getTrustedDeviceManager(): TrustedDevice
    {
        $key = 'trusted_device';
        if (!isset($this->resolvedInstances[$key])) {
            $storage = $this->config['trusted_device']['callback'] ?? function(){};
            $hmacKey = $this->config['trusted_device']['hmac_key'] ?? '';
            $this->resolvedInstances[$key] = new TrustedDevice($storage, $hmacKey);
        }
        return $this->resolvedInstances[$key];
    }

    /**
     * Get the Rate Limiter.
     *
     * @return DatabaseRateLimiter
     */
    public function getRateLimiter(callable $storage = null): DatabaseRateLimiter
    {
        $lockCallback = $this->config['rate_limit']['lock_callback'] ?? null;

        if ($storage !== null) {
            return new DatabaseRateLimiter($storage, $lockCallback);
        }

        $key = 'rate_limiter';
        if (!isset($this->resolvedInstances[$key])) {
            $storage = $this->config['rate_limit']['callback'] ?? function(){};
            $this->resolvedInstances[$key] = new DatabaseRateLimiter($storage, $lockCallback);
        }
        return $this->resolvedInstances[$key];
    }

    /**
     * Get the event dispatcher.
     *
     * @return Dispatcher
     */
    public function events(): Dispatcher
    {
        return $this->events;
    }

    /**
     * Register a custom driver creator.
     *
     * @param string $driver
     * @param callable $callback
     * @return $this
     */
    public function extend(string $driver, callable $callback)
    {
        $this->customCreators[$driver] = $callback;
        return $this;
    }
}

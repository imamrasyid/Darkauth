<?php

namespace Darkauth\Auth;

use Darkauth\Core\GuardInterface;
use Darkauth\Drivers\SessionGuard;
use Darkauth\Drivers\JWTGuard;
use Darkauth\Auth\DatabaseUserProvider;
use Darkauth\Core\UserProviderInterface;
use Darkauth\Events\Dispatcher;
use Darkauth\Audit\AuditLogger;
use Darkauth\Security\RateLimiterInterface;
use Darkauth\Support\SessionStorage;
use Darkauth\Support\JwtHelper;
use InvalidArgumentException;

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
        $jwtHelper = new JwtHelper(
            $jwtConfig['secret'] ?? 'change-me',
            $jwtConfig['algo'] ?? 'HS256'
        );

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

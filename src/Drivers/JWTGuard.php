<?php

namespace Darkauth\Drivers;

use Darkauth\Core\GuardInterface;
use Darkauth\Core\UserInterface;
use Darkauth\Core\UserProviderInterface;
use Darkauth\Support\JwtHelper;

/**
 * Class JWTGuard
 * 
 * Stateless authentication guard using JSON Web Tokens.
 */
class JWTGuard implements GuardInterface
{
    /**
     * @var JwtHelper
     */
    protected $jwt;

    /**
     * @var UserProviderInterface
     */
    protected $provider;

    /**
     * @var string|null
     */
    protected $token;

    /**
     * @var UserInterface|null
     */
    protected $user;

    /**
     * JWTGuard constructor.
     *
     * @param JwtHelper $jwt
     * @param UserProviderInterface $provider
     */
    public function __construct(JwtHelper $jwt, UserProviderInterface $provider)
    {
        $this->jwt = $jwt;
        $this->provider = $provider;
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        return !is_null($this->user());
    }

    /**
     * @inheritDoc
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * @inheritDoc
     */
    public function user(): ?UserInterface
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();

        if ($token) {
            $payload = $this->jwt->decode($token);
            if ($payload && isset($payload['sub'])) {
                $this->user = $this->provider->retrieveById($payload['sub']);
            }
        }

        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function id()
    {
        return $this->user() ? $this->user()->getAuthIdentifier() : null;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        return $user && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * @inheritDoc
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * Issue a new token for the given user.
     *
     * @param UserInterface $user
     * @param array $extraPayload
     * @return string
     */
    public function issueToken(UserInterface $user, array $extraPayload = []): string
    {
        $payload = array_merge($extraPayload, [
            'sub' => $user->getAuthIdentifier()
        ]);

        return $this->jwt->generate($payload);
    }

    /**
     * Set the token manually.
     *
     * @param string $token
     * @return $this
     */
    public function setToken(string $token)
    {
        $this->token = $token;
        $this->user = null; // Important: reset cached user when token changes
        return $this;
    }

    /**
     * Get the token from the request headers or manually set token.
     *
     * @return string|null
     */
    protected function getTokenFromRequest(): ?string
    {
        if ($this->token) {
            return $this->token;
        }

        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

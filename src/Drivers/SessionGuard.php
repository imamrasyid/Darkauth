<?php

namespace Darkauth\Drivers;

use Darkauth\Core\StatefulGuardInterface;
use Darkauth\Core\UserProviderInterface;
use Darkauth\Core\UserInterface;
use Darkauth\Core\StorageInterface;
use Darkauth\Events\Dispatcher;

/**
 * Class SessionGuard
 * 
 * Authentication guard using sessions for state management.
 */
class SessionGuard implements StatefulGuardInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var UserProviderInterface
     */
    protected $provider;

    /**
     * @var Dispatcher|null
     */
    protected $events;

    /**
     * @var UserInterface|null
     */
    protected $user;

    /**
     * SessionGuard constructor.
     *
     * @param string $name
     * @param StorageInterface $storage
     * @param UserProviderInterface $provider
     * @param Dispatcher|null $events
     */
    public function __construct(string $name, StorageInterface $storage, UserProviderInterface $provider, Dispatcher $events = null)
    {
        $this->name = $name;
        $this->storage = $storage;
        $this->provider = $provider;
        $this->events = $events;
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

        $id = $this->storage->get($this->getName() . '_user_id');

        if ($id !== null) {
            $this->user = $this->provider->retrieveById($id);
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
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            $this->login($user, $remember);
            return true;
        }

        if ($this->events) {
            $this->events->dispatch('auth.login.failed', ['credentials' => $credentials]);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function login(UserInterface $user, bool $remember = false)
    {
        $this->storage->set($this->getName() . '_user_id', $user->getAuthIdentifier());
        
        // Prevent session fixation
        $this->storage->regenerate(true);
        
        if ($this->events) {
            $this->events->dispatch('auth.login.success', ['user' => $user]);
        }

        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function loginUsingId($id, bool $remember = false)
    {
        $user = $this->provider->retrieveById($id);

        if ($user) {
            $this->login($user, $remember);
            return $user;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function logout()
    {
        $user = $this->user();
        $this->storage->remove($this->getName() . '_user_id');
        $this->user = null;

        if ($this->events && $user) {
            $this->events->dispatch('auth.logout', ['user' => $user]);
        }
    }

    /**
     * @inheritDoc
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * Get the guard name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'darkauth_' . $this->name;
    }
}

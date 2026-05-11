<?php

namespace Darkauth\Core;

/**
 * Interface StatefulGuardInterface
 * 
 * Extends GuardInterface to include methods for managing authentication state (sessions).
 */
interface StatefulGuardInterface extends GuardInterface
{
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     */
    public function attempt(array $credentials = [], bool $remember = false): bool;

    /**
     * Log a user into the application.
     *
     * @param UserInterface $user
     * @param bool $remember
     * @return void
     */
    public function login(UserInterface $user, bool $remember = false);

    /**
     * Log the given user ID into the application.
     *
     * @param mixed $id
     * @param bool $remember
     * @return UserInterface|false
     */
    public function loginUsingId($id, bool $remember = false);

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout();
}

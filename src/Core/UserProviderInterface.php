<?php

namespace Darkauth\Core;

/**
 * Interface UserProviderInterface
 * 
 * Defines how to retrieve users for authentication.
 */
interface UserProviderInterface
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param mixed $identifier
     * @return UserInterface|null
     */
    public function retrieveById($identifier): ?UserInterface;

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     * @return UserInterface|null
     */
    public function retrieveByCredentials(array $credentials): ?UserInterface;

    /**
     * Validate a user against the given credentials.
     *
     * @param UserInterface $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(UserInterface $user, array $credentials): bool;
}

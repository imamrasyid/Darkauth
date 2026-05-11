<?php

namespace Darkauth\Core;

/**
 * Interface GuardInterface
 * 
 * Defines the contract for an authentication guard.
 */
interface GuardInterface
{
    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check(): bool;

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest(): bool;

    /**
     * Get the currently authenticated user.
     *
     * @return UserInterface|null
     */
    public function user(): ?UserInterface;

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return mixed|null
     */
    public function id();

    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool;

    /**
     * Set the current user.
     *
     * @param UserInterface $user
     * @return void
     */
    public function setUser(UserInterface $user);
}

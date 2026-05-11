<?php

namespace Darkauth\Core;

/**
 * Interface UserInterface
 * 
 * Defines the contract for an authenticatable user.
 */
interface UserInterface
{
    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword();

    /**
     * Get the "remember me" token for the user.
     *
     * @return string|null
     */
    public function getRememberToken();

    /**
     * Set the "remember me" token for the user.
     *
     * @param string $value
     * @return void
     */
    public function setRememberToken(string $value);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName();
}

<?php

namespace Darkauth\Core;

/**
 * Trait AuthenticatableTrait
 * 
 * Provides default implementation for UserInterface.
 */
trait AuthenticatableTrait
{
    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->id ?? null;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password ?? '';
    }

    /**
     * Get the "remember me" token for the user.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return $this->remember_token ?? null;
    }

    /**
     * Set the "remember me" token for the user.
     *
     * @param string $value
     * @return void
     */
    public function setRememberToken(string $value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}

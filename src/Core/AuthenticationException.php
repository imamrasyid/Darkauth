<?php

namespace Darkauth\Core;

use Exception;

/**
 * Class AuthenticationException
 * 
 * Thrown when authentication fails.
 */
class AuthenticationException extends Exception
{
    /**
     * @var array
     */
    protected $guards;

    /**
     * Create a new authentication exception.
     *
     * @param string $message
     * @param array $guards
     */
    public function __construct(string $message = 'Unauthenticated.', array $guards = [])
    {
        parent::__construct($message, 401);
        $this->guards = $guards;
    }

    /**
     * Get the affected guards.
     *
     * @return array
     */
    public function guards(): array
    {
        return $this->guards;
    }
}

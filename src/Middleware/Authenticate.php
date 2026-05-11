<?php

namespace Darkauth\Middleware;

use Darkauth\Auth\AuthManager;
use Exception;

/**
 * Class Authenticate
 * 
 * Middleware to ensure the user is authenticated.
 */
class Authenticate
{
    /**
     * @var AuthManager
     */
    protected $auth;

    /**
     * Authenticate constructor.
     *
     * @param AuthManager $auth
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle the request.
     *
     * @param string|null $guard
     * @throws Exception
     */
    public function handle(string $guard = null)
    {
        if ($this->auth->guard($guard)->guest()) {
            throw new Exception("Unauthorized. Please log in.");
        }
    }
}

<?php

namespace Darkauth\Support;

use Darkauth\Auth\AuthManager;
use Darkauth\Core\GuardInterface;

/**
 * Class CI3Auth
 * 
 * A CodeIgniter 3 compatible wrapper for the Darkauth library.
 */
class CI3Auth
{
    /**
     * @var AuthManager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $config;

    /**
     * CI3Auth constructor.
     *
     * @param array $params Optional configuration
     */
    public function __construct(array $params = [])
    {
        // In CI3, we usually get the instance and load config
        if (function_exists('get_instance')) {
            $CI =& get_instance();
            $CI->config->load('darkauth', TRUE, TRUE);
            $ciConfig = $CI->config->item('darkauth');
            $this->config = array_merge($ciConfig ?: [], $params);
        } else {
            $this->config = $params;
        }

        // If no config provided, load default from the library
        if (empty($this->config)) {
            $this->config = require __DIR__ . '/../../config/auth.php';
        }

        $this->manager = new AuthManager($this->config);
    }

    /**
     * Get a guard instance.
     *
     * @param string|null $name
     * @return GuardInterface
     */
    public function guard(string $name = null): GuardInterface
    {
        return $this->manager->guard($name);
    }

    /**
     * Dynamically proxy method calls to the default guard.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->guard()->{$method}(...$parameters);
    }

    /**
     * Get the AuthManager instance.
     *
     * @return AuthManager
     */
    public function getManager(): AuthManager
    {
        return $this->manager;
    }
}

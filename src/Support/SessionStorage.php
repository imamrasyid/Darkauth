<?php

namespace Darkauth\Support;

use Darkauth\Core\StorageInterface;

/**
 * Class SessionStorage
 * 
 * Implementation of StorageInterface using PHP native sessions.
 */
class SessionStorage implements StorageInterface
{
    /**
     * SessionStorage constructor.
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key)
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $_SESSION = [];
    }

    /**
     * @inheritDoc
     */
    public function regenerate(bool $destroy = false)
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id($destroy);
        }
    }
}

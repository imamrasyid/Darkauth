<?php

namespace Darkauth\Core;

/**
 * Interface StorageInterface
 * 
 * Defines the contract for authentication storage (e.g., Session, Database, Redis).
 */
interface StorageInterface
{
    /**
     * Retrieve a value from storage.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Store a value in storage.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value);

    /**
     * Remove a value from storage.
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key);

    /**
     * Check if a key exists in storage.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Clear all values from storage.
     *
     * @return void
     */
    public function clear();

    /**
     * Regenerate the storage identifier (e.g., session ID).
     *
     * @param bool $destroy Whether to destroy the old session data
     * @return void
     */
    public function regenerate(bool $destroy = false);
}

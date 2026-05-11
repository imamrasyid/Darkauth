<?php

namespace Darkauth\Support;

/**
 * Class Hash
 * 
 * Simple wrapper for PHP password hashing functions.
 */
class Hash
{
    /**
     * Hash the given value.
     *
     * @param string $value
     * @param array $options
     * @return string
     */
    public static function make(string $value, array $options = []): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_hash($value, $algo, $options);
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value
     * @param string $hashedValue
     * @return bool
     */
    public static function check(string $value, string $hashedValue): bool
    {
        if (strlen($hashedValue) === 0) {
            return false;
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param string $hashedValue
     * @param array $options
     * @return bool
     */
    public static function needsRehash(string $hashedValue, array $options = []): bool
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_needs_rehash($hashedValue, $algo, $options);
    }

    /**
     * Generate a secure random string.
     *
     * @param int $length
     * @return string
     */
    public static function randomToken(int $length = 40): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Compare two strings in a timing-safe manner.
     *
     * @param string $knownString
     * @param string $userString
     * @return bool
     */
    public static function equals(string $knownString, string $userString): bool
    {
        return hash_equals($knownString, $userString);
    }
}

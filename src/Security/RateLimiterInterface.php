<?php

namespace Darkauth\Security;

/**
 * Interface RateLimiterInterface
 */
interface RateLimiterInterface
{
    /**
     * Check if the given key has exceeded its rate limit.
     *
     * @param string $key
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return bool
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool;

    /**
     * Increment the counter for the given key.
     *
     * @param string $key
     * @param int $decaySeconds
     * @return int
     */
    public function hit(string $key, int $decaySeconds = 60): int;

    /**
     * Get the number of remaining attempts for the given key.
     *
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    public function remaining(string $key, int $maxAttempts): int;

    /**
     * Clear the attempts for the given key.
     *
     * @param string $key
     * @return void
     */
    public function clear(string $key);

    /**
     * Get the number of seconds until the next attempt is allowed.
     *
     * @param string $key
     * @return int
     */
    public function availableIn(string $key): int;
}

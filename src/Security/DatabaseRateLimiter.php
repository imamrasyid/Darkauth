<?php

namespace Darkauth\Security;

/**
 * Class DatabaseRateLimiter
 * 
 * Simple rate limiter using a database/callback mechanism.
 */
class DatabaseRateLimiter implements RateLimiterInterface
{
    /**
     * @var callable
     */
    protected $storage;

    /**
     * DatabaseRateLimiter constructor.
     *
     * @param callable $storage Callback to get/set limit data
     */
    public function __construct(callable $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @inheritDoc
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $data = $this->get($key);
        return $data && $data['attempts'] >= $maxAttempts && $data['expires_at'] > time();
    }

    /**
     * @inheritDoc
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        $data = $this->get($key);

        if (!$data || $data['expires_at'] <= time()) {
            $data = ['attempts' => 1, 'expires_at' => time() + $decaySeconds];
        } else {
            $data['attempts']++;
        }

        $this->set($key, $data);
        return $data['attempts'];
    }

    /**
     * @inheritDoc
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        $data = $this->get($key);
        if (!$data) return $maxAttempts;
        return max(0, $maxAttempts - $data['attempts']);
    }

    /**
     * @inheritDoc
     */
    public function clear(string $key)
    {
        $this->set($key, null);
    }

    /**
     * @inheritDoc
     */
    public function availableIn(string $key): int
    {
        $data = $this->get($key);
        if (!$data) return 0;
        return max(0, $data['expires_at'] - time());
    }

    protected function get(string $key)
    {
        return call_user_func($this->storage, 'get', $key);
    }

    protected function set(string $key, $value)
    {
        call_user_func($this->storage, 'set', $key, $value);
    }
}

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
     * @var callable|null
     */
    protected $lockCallback;

    /**
     * DatabaseRateLimiter constructor.
     *
     * @param callable $storage Callback to get/set limit data
     * @param callable|null $lockCallback Optional lock callback: function($key, callable $fn)
     */
    public function __construct(callable $storage, callable $lockCallback = null)
    {
        $this->storage = $storage;
        $this->lockCallback = $lockCallback;
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
        if ($decaySeconds <= 0) {
            $decaySeconds = 60;
        }

        if ($this->lockCallback) {
            return call_user_func($this->lockCallback, $key, function() use ($key, $decaySeconds) {
                return $this->doHit($key, $decaySeconds);
            });
        }
        return $this->doHit($key, $decaySeconds);
    }

    /**
     * Perform the actual hit operation (must be called within a lock if available).
     */
    protected function doHit(string $key, int $decaySeconds): int
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
        if (!$data || $data['expires_at'] <= time()) return $maxAttempts;
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

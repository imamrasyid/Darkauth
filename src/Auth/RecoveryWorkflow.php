<?php

namespace Darkauth\Auth;

use Darkauth\Support\Hash;
use Darkauth\Events\Dispatcher;

/**
 * Class RecoveryWorkflow
 * 
 * Manages secure recovery workflows (Password reset, Account recovery).
 */
class RecoveryWorkflow
{
    /**
     * @var callable
     */
    protected $storageCallback;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * RecoveryWorkflow constructor.
     *
     * @param callable $storageCallback Storage callback: function($action, $key, $value = null)
     * @param Dispatcher $events
     */
    public function __construct(callable $storageCallback, Dispatcher $events)
    {
        $this->storageCallback = $storageCallback;
        $this->events = $events;
    }

    /**
     * Create a recovery token for the user.
     *
     * @param mixed $userId
     * @param int $expirySeconds
     * @return string
     */
    public function createToken($userId, int $expirySeconds = 3600): string
    {
        if ($userId === null || $userId === '') {
            throw new \InvalidArgumentException('User ID cannot be empty for recovery token creation.');
        }

        $token = Hash::randomToken(64);
        
        $this->set('recovery_' . $userId, [
            'token' => $token,
            'expires' => time() + $expirySeconds
        ]);

        $this->events->dispatch('auth.recovery.token_created', ['user_id' => $userId]);

        return $token;
    }

    /**
     * Verify the recovery token.
     *
     * @param mixed $userId
     * @param string $token
     * @return bool
     */
    public function verifyToken($userId, string $token): bool
    {
        $data = $this->get('recovery_' . $userId);

        if (!$data || $data['expires'] < time()) {
            return false;
        }

        $isValid = Hash::equals($data['token'], $token);

        if ($isValid) {
            $this->events->dispatch('auth.recovery.token_verified', ['user_id' => $userId]);
        }

        return $isValid;
    }

    /**
     * Complete the recovery (e.g., after password reset).
     * Requires a verified token to prevent unauthorized recovery completion.
     *
     * @param mixed $userId
     * @param string $token Recovery token that was verified
     * @return bool True if recovery completed successfully
     */
    public function complete($userId, string $token = null): bool
    {
        // ponytail: token-less completion kept for BC; insecure. Remove when callers migrate.
        if ($token === null) {
            error_log('DarkAuth: RecoveryWorkflow::complete() without $token is deprecated and insecure; pass the verified recovery token.');
            $this->remove('recovery_' . $userId);
            $this->events->dispatch('auth.recovery.completed', ['user_id' => $userId]);
            return true;
        }

        if (!$this->verifyToken($userId, $token)) {
            return false;
        }

        $this->remove('recovery_' . $userId);
        $this->events->dispatch('auth.recovery.completed', ['user_id' => $userId]);
        return true;
    }

    protected function get(string $key): ?array
    {
        return call_user_func($this->storageCallback, 'get', $key);
    }

    protected function set(string $key, $value): void
    {
        call_user_func($this->storageCallback, 'set', $key, $value);
    }

    protected function remove(string $key): void
    {
        call_user_func($this->storageCallback, 'remove', $key);
    }
}

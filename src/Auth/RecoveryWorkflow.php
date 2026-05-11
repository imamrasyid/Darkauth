<?php

namespace Darkauth\Auth;

use Darkauth\Support\Hash;
use Darkauth\Core\StorageInterface;
use Darkauth\Events\Dispatcher;

/**
 * Class RecoveryWorkflow
 * 
 * Manages secure recovery workflows (Password reset, Account recovery).
 */
class RecoveryWorkflow
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * RecoveryWorkflow constructor.
     */
    public function __construct(StorageInterface $storage, Dispatcher $events)
    {
        $this->storage = $storage;
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
        $token = Hash::randomToken(64);
        
        $this->storage->set('recovery_' . $userId, [
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
        $data = $this->storage->get('recovery_' . $userId);

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
     *
     * @param mixed $userId
     * @return void
     */
    public function complete($userId)
    {
        $this->storage->remove('recovery_' . $userId);
        $this->events->dispatch('auth.recovery.completed', ['user_id' => $userId]);
    }
}

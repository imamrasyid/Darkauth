<?php

namespace Darkauth\Security;

use Darkauth\Support\Hash;

/**
 * Class TrustedDevice
 * 
 * Handles registration and verification of trusted devices.
 */
class TrustedDevice
{
    /**
     * @var callable
     */
    protected $storageCallback;

    /**
     * @var string
     */
    protected $hmacKey;

    /**
     * TrustedDevice constructor.
     *
     * @param callable $storageCallback Persistent storage callback: function($action, $key, $value = null)
     * @param string $hmacKey Key for hashing device tokens
     */
    public function __construct(callable $storageCallback, string $hmacKey = '')
    {
        if ($hmacKey === '') {
            throw new \InvalidArgumentException('TrustedDevice requires a non-empty HMAC key to produce secure device token hashes.');
        }
        $this->storageCallback = $storageCallback;
        $this->hmacKey = $hmacKey;
    }

    /**
     * Issue a new trusted device token.
     *
     * @param mixed $userId
     * @param int $duration Days
     * @return string
     */
    public function issue($userId, int $duration = 30): string
    {
        $token = Hash::randomToken(64);
        $expiry = time() + ($duration * 86400);

        $tokenHash = hash_hmac('sha256', $token, $this->hmacKey);
        $this->set('device_' . $userId . '_' . $tokenHash, [
            'expires' => $expiry,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'created_at' => time()
        ]);

        return $token;
    }

    /**
     * Verify if the current device is trusted for the user.
     *
     * @param mixed $userId
     * @param string $token
     * @return bool
     */
    public function verify($userId, string $token): bool
    {
        $tokenHash = hash_hmac('sha256', $token, $this->hmacKey);
        $data = $this->get('device_' . $userId . '_' . $tokenHash);

        if (!$data || $data['expires'] < time()) {
            return false;
        }

        if ($this->isRevoked($userId, $tokenHash)) {
            return false;
        }

        return $data['ua'] === ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
    }

    /**
     * Remove all trusted devices for a user.
     *
     * @param mixed $userId
     * @return void
     */
    public function revokeAll($userId)
    {
        $this->set('device_revoke_all_' . $userId, ['revoke_all' => true, 'at' => time()]);
    }

    /**
     * Check if all devices for a user have been revoked.
     *
     * @param mixed $userId
     * @param string $tokenHash
     * @return bool
     */
    public function isRevoked($userId, string $tokenHash): bool
    {
        $data = $this->get('device_revoke_all_' . $userId);
        if (!$data || !isset($data['revoke_all'])) {
            return false;
        }
        $deviceData = $this->get('device_' . $userId . '_' . $tokenHash);
        return $deviceData && isset($deviceData['created_at']) && $deviceData['created_at'] <= $data['at'];
    }

    protected function get(string $key)
    {
        return call_user_func($this->storageCallback, 'get', $key);
    }

    protected function set(string $key, $value)
    {
        call_user_func($this->storageCallback, 'set', $key, $value);
    }
}

<?php

namespace Darkauth\Security;

use Darkauth\Support\Hash;
use Darkauth\Core\StorageInterface;

/**
 * Class TrustedDevice
 * 
 * Handles registration and verification of trusted devices.
 */
class TrustedDevice
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var string
     */
    protected $cookieName = 'darkauth_device_token';

    /**
     * TrustedDevice constructor.
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
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

        // Store token hash for security
        $this->storage->set('device_' . $userId . '_' . md5($token), [
            'expires' => $expiry,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
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
        $data = $this->storage->get('device_' . $userId . '_' . md5($token));

        if (!$data || $data['expires'] < time()) {
            return false;
        }

        // Browser UA check for extra security (lightweight fingerprinting)
        return $data['ua'] === ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
    }
}

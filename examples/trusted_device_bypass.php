<?php

/**
 * Example: Trusted Device Management
 *
 * "Remember this device" untuk bypass MFA.
 * Token di-sign dengan HMAC agar tidak bisa dipalsukan.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Security\TrustedDevice;
use Darkauth\Support\SessionStorage;

// ── Setup ────────────────────────────────────────────────────────
$storage = new SessionStorage();
$hmacKey = bin2hex(random_bytes(32)); // Gunakan key tetap di produksi
$storageCallback = function ($action, $key, $value = null) use ($storage) {
    switch ($action) {
        case 'get':    return $storage->get($key);
        case 'set':    $storage->set($key, $value); break;
        case 'remove': $storage->remove($key); break;
    }
};
$device = new TrustedDevice($storageCallback, $hmacKey);

$userId = 101;

// ── Skenario 1: Issue device token ──────────────────────────────
echo "=== Skenario 1: Issue Trusted Device Token ===\n";
$days = 30;
$token = $device->issue($userId, $days);
echo "Token issued (30 hari): {$token}\n";
echo "(Simpan di cookie: device_token={$token})\n\n";

// ── Skenario 2: Verifikasi token ────────────────────────────────
echo "=== Skenario 2: Verify Device Token ===\n";
if ($device->verify($userId, $token)) {
    echo "Device TRUSTED! MFA dapat di-bypass.\n";
    echo "Action: skip_mfa_verification()\n";
} else {
    echo "Device NOT trusted. MFA required.\n";
}

// ── Skenario 3: Token salah ─────────────────────────────────────
echo "\n=== Skenario 3: Invalid Token ===\n";
$fakeToken = 'fake.device.token.12345';
if (!$device->verify($userId, $fakeToken)) {
    echo "Correctly rejected fake token.\n";
}

// ── Skenario 4: Revoke semua device ─────────────────────────────
echo "\n=== Skenario 4: Revoke All Devices ===\n";
$device->revokeAll($userId);
echo "All devices revoked for user #{$userId}\n";

// Verifikasi lagi (sudah di-revoke)
if (!$device->verify($userId, $token)) {
    echo "Token no longer valid after revokeAll().\n";
}

// ── Skenario 5: Multi-device ────────────────────────────────────
echo "\n=== Skenario 5: Multi-Device ===\n";
$device2 = new TrustedDevice($storageCallback, $hmacKey);

$token1 = $device2->issue($userId, 7);
$token2 = $device2->issue($userId, 30);

echo "Device 1 token (7 hari): " . substr($token1, 0, 20) . "...\n";
echo "Device 2 token (30 hari): " . substr($token2, 0, 20) . "...\n";

// Kedua device bisa diverifikasi
$device2->revokeAll($userId); // Reset dulu
$token1 = $device2->issue($userId, 7);
$token2 = $device2->issue($userId, 30);

echo "Device 1 trusted: " . ($device2->verify($userId, $token1) ? 'Yes' : 'No') . "\n";
echo "Device 2 trusted: " . ($device2->verify($userId, $token2) ? 'Yes' : 'No') . "\n";

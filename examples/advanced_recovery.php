<?php

/**
 * Example: Password Recovery Workflow
 *
 * Alur lengkap reset password via email:
 * 1. User minta reset → generate token
 * 2. Kirim email dengan link
 * 3. User klik link → verifikasi token
 * 4. User submit password baru → invalidate token
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Auth\RecoveryWorkflow;
use Darkauth\Events\Dispatcher;
use Darkauth\Support\SessionStorage;
use Darkauth\Support\Hash;

// ── Setup ────────────────────────────────────────────────────────
$storage = new SessionStorage();
$events = new Dispatcher();
$recovery = new RecoveryWorkflow(function ($action, $key, $value = null) use ($storage) {
    switch ($action) {
        case 'get':    return $storage->get($key);
        case 'set':    $storage->set($key, $value); break;
        case 'remove': $storage->remove($key); break;
    }
}, $events);

// ── STEP 1: User minta reset password ────────────────────────────
echo "=== STEP 1: Request Password Reset ===\n";
$userId = 101;
$expiry = 900; // 15 menit

$token = $recovery->createToken($userId, $expiry);
echo "Token: {$token}\n";
echo "Link  : https://myapp.com/reset-password?token={$token}&id={$userId}\n";
echo "(Email dikirim ke user)\n\n";

// ── STEP 2: User klik link dari email ────────────────────────────
echo "=== STEP 2: Verify Token ===\n";
$inputToken = $token; // Simulasi: user kirim token dari URL

if ($recovery->verifyToken($userId, $inputToken)) {
    echo "Token valid! User diizinkan mengubah password.\n\n";

    // ── STEP 3: User submit password baru ────────────────────────
    echo "=== STEP 3: Change Password ===\n";
    $newPassword = 'NewSecurePassword123!';
    $hashedPassword = Hash::make($newPassword);

    // Update password di database
    // $this->db->update('users', ['password' => $hashedPassword], ['id' => $userId]);
    echo "Password updated (hashed).\n";

    // ── STEP 4: Invalidate token ────────────────────────────────
    echo "\n=== STEP 4: Complete Recovery ===\n";
    $recovery->complete($userId, $inputToken);
    echo "Token invalidated. Recovery flow selesai.\n";

} else {
    echo "Token tidak valid atau sudah kedaluwarsa.\n";
}

// ── Test: Coba gunakan token lagi (sudah di-invalidate) ──────────
echo "\n=== Test: Reuse Invalidated Token ===\n";
if (!$recovery->verifyToken($userId, $token)) {
    echo "Correctly rejected: token sudah tidak berlaku.\n";
}

// ── Edge Case: Token kedaluwarsa ────────────────────────────────
echo "\n=== Edge Case: Expired Token ===\n";
$shortLived = $recovery->createToken($userId, 1); // 1 detik
sleep(2);

if (!$recovery->verifyToken($userId, $shortLived)) {
    echo "Correctly rejected: token expired.\n";
}

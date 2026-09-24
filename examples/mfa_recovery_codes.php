<?php

/**
 * Example: MFA Recovery Codes
 *
 * Backup codes saat user tidak punya akses ke authenticator app.
 * Wajib ditampilkan ke user sekali saja saat setup MFA.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\MFA\RecoveryCodes;
use Darkauth\MFA\TOTPDriver;
use Darkauth\Support\Hash;
use Darkauth\Support\UI\Templates;

// ── Simulasi storage ────────────────────────────────────────────
$storedCodes = [];

function saveRecoveryCode($userId, $code) {
    global $storedCodes;
    $storedCodes[] = [
        'user_id' => $userId,
        'code_hash' => Hash::make($code),
        'used' => false,
    ];
}

function verifyRecoveryCode($userId, $inputCode) {
    global $storedCodes;
    foreach ($storedCodes as &$entry) {
        if ($entry['user_id'] === $userId && !$entry['used']) {
            if (Hash::check($inputCode, $entry['code_hash'])) {
                $entry['used'] = true;
                return true;
            }
        }
    }
    return false;
}

// ── STEP 1: Generate recovery codes saat setup MFA ───────────────
echo "=== STEP 1: Generate Recovery Codes ===\n";
$recovery = new RecoveryCodes();
$codes = $recovery->generate(8);

echo "Generated " . count($codes) . " recovery codes:\n";
foreach ($codes as $i => $code) {
    echo "  " . ($i + 1) . ". {$code}\n";
}

// ── STEP 2: Simpan hashed codes ke database ──────────────────────
echo "\n=== STEP 2: Simpan ke Database (hashed) ===\n";
$userId = 101;
foreach ($codes as $code) {
    saveRecoveryCode($userId, $code);
}
echo "Tersimpan: " . count($storedCodes) . " codes\n";

// ── STEP 3: Render UI untuk user ────────────────────────────────
echo "\n=== STEP 3: Render Recovery Codes UI ===\n";
// Uncomment untuk render di browser:
// echo Templates::styles();
// echo Templates::recoveryCodes($codes);

// ── STEP 4: Verifikasi recovery code ────────────────────────────
echo "\n=== STEP 4: Verifikasi Recovery Code ===\n";
$testCode = $codes[0]; // Ambil kode pertama

if (verifyRecoveryCode($userId, $testCode)) {
    echo "Recovery code valid! User dapat login.\n";
    // Proses login user...
} else {
    echo "Recovery code tidak valid.\n";
}

// Coba lagi dengan kode yang sama (sudah dipakai)
if (!verifyRecoveryCode($userId, $testCode)) {
    echo "Code sudah digunakan (sekali pakai).\n";
}

// ── STEP 5: Cek sisa codes ──────────────────────────────────────
echo "\n=== Sisa Recovery Codes ===\n";
$remaining = 0;
foreach ($storedCodes as $entry) {
    if (!$entry['used']) {
        $remaining++;
    }
}
echo "Tersisa: {$remaining} codes\n";
echo "Tips: Sisa codes harus ditampilkan saat user cek settings\n";

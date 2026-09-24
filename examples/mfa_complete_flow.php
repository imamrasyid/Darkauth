<?php

/**
 * Example: MFA TOTP Setup Flow
 *
 * Alur lengkap setup Google Authenticator / Authy:
 * 1. Generate secret
 * 2. Render QR code
 * 3. Verifikasi kode pertama dari user
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\MFA\TOTPDriver;
use Darkauth\Support\UI\Templates;

$mfa = new TOTPDriver();

// ── STEP 1: Generate secret ─────────────────────────────────────
echo "=== STEP 1: Generate Secret ===\n";
$secret = $mfa->generateSecret();
echo "Secret (simpan ke database): {$secret}\n\n";

// ── STEP 2: Generate QR code URL ────────────────────────────────
echo "=== STEP 2: QR Code URL ===\n";
$qrUrl = $mfa->getQrCodeUrl(
    'pegawai_negeri_123',    // Username
    $secret,                  // Secret
    'Sistem Kepegawaian'     // Issuer name
);
echo "QR URL: {$qrUrl}\n\n";

// ── STEP 3: Render UI ───────────────────────────────────────────
echo "=== STEP 3: Render UI (HTML) ===\n";
// Uncomment untuk render di browser:
// echo Templates::styles();
// echo Templates::mfaOnboarding($qrUrl, $secret);

// ── STEP 4: Verifikasi kode ─────────────────────────────────────
echo "=== STEP 4: Verifikasi Kode ===\n";

// Simulasi: user memasukkan kode dari authenticator app
// Dalam aplikasi nyata, kode ini dikirim via POST
$inputCode = '123456'; // Ganti dengan kode asli untuk test
$isVerified = $mfa->verify($secret, $inputCode);

if ($isVerified) {
    echo "MFA berhasil diaktifkan!\n";
    // Simpan $secret ke database user
    // $this->db->update('users', ['mfa_secret' => $secret, 'mfa_enabled' => 1], ['id' => $userId]);
} else {
    echo "Kode tidak valid. User harus mencoba lagi.\n";
}

// ── STEP 5: Cek apakah perlu rehash ─────────────────────────────
echo "\n=== Info ===\n";
echo "Secret stored di database (hashed)\n";
echo "User sekarang wajib memasukkan kode TOTP saat login\n";

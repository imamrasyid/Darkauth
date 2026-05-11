<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\MFA\TOTPDriver;
use Darkauth\Support\UI\Templates;

/**
 * Example: Complete MFA (TOTP) Setup Flow
 */

$mfa = new TOTPDriver();

// 1. Simulation: User wants to enable MFA
// Usually you store this secret in your database for the user
$userSecret = $mfa->generateSecret();
$qrCodeUrl = $mfa->getQrCodeUrl('pegawai_negeri_123', $userSecret, 'Sistem Kepegawaian');

// 2. Render UI (using the Government-style template)
echo Templates::styles();
echo Templates::mfaOnboarding($qrCodeUrl, $userSecret);

// 3. Simulation: User enters the 6-digit code from their phone
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputCode = $_POST['mfa_code'] ?? '';
    
    if ($mfa->verify($userSecret, $inputCode)) {
        echo "<div class='darkauth-container' style='border-color:green;'>";
        echo "<h2 style='color:green;'>✅ Berhasil!</h2>";
        echo "<p>MFA telah aktif. Akun Anda sekarang jauh lebih aman.</p>";
        echo "</div>";
    } else {
        echo "<div class='darkauth-container' style='border-color:red;'>";
        echo "<h2 style='color:red;'>❌ Kode Salah</h2>";
        echo "<p>Kode yang Anda masukkan tidak valid. Silakan coba lagi.</p>";
        echo "</div>";
    }
}

<?php

/**
 * Example: CAPTCHA Integration
 *
 * Google reCAPTCHA v2 integration untuk proteksi form.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Captcha\ReCaptchaDriver;

// ── Setup ────────────────────────────────────────────────────────
$siteKey = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'; // Google test key
$secretKey = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Google test key

$captcha = new ReCaptchaDriver($siteKey, $secretKey);

// ── Skenario 1: Render form ─────────────────────────────────────
echo "=== Skenario 1: Render CAPTCHA Form ===\n";
$renderedSiteKey = $captcha->getSiteKey();
echo "Site Key: {$renderedSiteKey}\n\n";

// HTML output (uncomment untuk browser):
echo "<!-- Copy ke view file -->\n";
echo "<form method='POST' action='/login'>\n";
echo "  <input type='text' name='username' placeholder='Username'>\n";
echo "  <input type='password' name='password' placeholder='Password'>\n";
echo "  <div class='g-recaptcha' data-sitekey='{$renderedSiteKey}'></div>\n";
echo "  <button type='submit'>Login</button>\n";
echo "</form>\n";
echo "<script src='https://www.google.com/recaptcha/api.js'></script>\n\n";

// ── Skenario 2: Verifikasi (server-side) ────────────────────────
echo "=== Skenario 2: Verifikasi CAPTCHA ===\n";

// Simulasi: dalam aplikasi nyata, ini dari $_POST['g-recaptcha-response']
$testResponse = 'test-valid-response'; // Ganti dengan response asli
$ip = '192.168.1.100';

// Catatan: Test key selalu return success
// Dalam produksi, gunakan secret key yang valid
echo "Response: {$testResponse}\n";
echo "IP: {$ip}\n\n";

// Verifikasi (akan call Google API)
// $isValid = $captcha->verify($testResponse, $ip);
// echo "Valid: " . ($isValid ? 'Yes' : 'No') . "\n";

echo "(Uncomment verify() call untuk test dengan key asli)\n\n";

// ── Skenario 3: Tanpa IP (anonymous) ────────────────────────────
echo "=== Skenario 3: Verifikasi Tanpa IP ===\n";
echo "Jika IP tidak diketahui, biarkan null:\n";
echo "  \$captcha->verify(\$response, null);\n";
echo "  \$captcha->verify(\$response); // Optional param\n\n";

// ── Skenario 4: Error handling ──────────────────────────────────
echo "=== Skenario 4: Integration Pattern ===\n";
echo "
// Di controller login:
public function login() {
    \$captcha = \$this->auth->captcha();
    
    // 1. Verifikasi CAPTCHA dulu
    if (!\$captcha->verify(\$_POST['g-recaptcha-response'], \$this->input->ip_address())) {
        show_error('CAPTCHA verification failed.', 400);
    }
    
    // 2. Baru proses login
    if (\$this->auth->attempt(\$credentials)) {
        redirect('dashboard');
    }
}
";

<?php

/**
 * Example: Adaptive Risk Engine & Security Profiles
 *
 * Deteksi login mencurigakan berdasarkan konteks:
 * - IP berubah (geolocation)
 * - User agent berubah
 * - Banyak percobaan gagal
 * - Waktu login tidak wajar
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Security\RiskEngine;
use Darkauth\Security\SecurityProfile;
use Darkauth\Models\GenericUser;

// ── Setup ────────────────────────────────────────────────────────
$riskEngine = new RiskEngine();
$profiles = new SecurityProfile();

// ── Skenario 1: Low risk (normal login) ─────────────────────────
echo "=== Skenario 1: Low Risk (Normal Login) ===\n";
$profile = $profiles->get('standard');

$context = [
    'ip'              => '192.168.1.100',
    'last_ip'         => '192.168.1.100',  // IP sama
    'ua'              => 'Mozilla/5.0 (Windows NT 10.0)',
    'recent_failures' => 0,
];

$score = $riskEngine->calculateScore(null, $context);
echo "Risk score: {$score}\n";

if ($riskEngine->shouldChallenge($score, $profile['captcha_threshold'] * 10)) {
    echo "Action: CAPTCHA required\n";
} else {
    echo "Action: Allow login directly\n";
}

// ── Skenario 2: Medium risk (IP berubah) ────────────────────────
echo "\n=== Skenario 2: Medium Risk (IP Changed) ===\n";
$context = [
    'ip'              => '203.0.113.42',  // IP berbeda
    'last_ip'         => '192.168.1.100',
    'ua'              => 'Mozilla/5.0 (Windows NT 10.0)',
    'recent_failures' => 2,
];

$score = $riskEngine->calculateScore(null, $context);
echo "Risk score: {$score}\n";

if ($riskEngine->shouldChallenge($score, $profile['captcha_threshold'] * 10)) {
    echo "Action: CAPTCHA required\n";
}

if ($riskEngine->shouldChallenge($score, 70)) {
    echo "Action: MFA verification required\n";
}

// ── Skenario 3: High risk (suspicious activity) ─────────────────
echo "\n=== Skenario 3: High Risk (Suspicious) ===\n";
$context = [
    'ip'              => '198.51.100.77',  // IP sangat berbeda
    'last_ip'         => '192.168.1.100',
    'ua'              => 'curl/7.68.0',     // Bot/scraping
    'recent_failures' => 8,                // Banyak gagal
];

$score = $riskEngine->calculateScore(null, $context);
echo "Risk score: {$score}\n";

if ($riskEngine->shouldChallenge($score, 30)) {
    echo "Action: Block login, notify admin\n";
}

// ── Skenario 4: Security Profiles ───────────────────────────────
echo "\n=== Security Profiles ===\n";
$levels = ['basic', 'standard', 'hardened', 'government'];

foreach ($levels as $level) {
    $p = $profiles->get($level);
    echo sprintf(
        "\n%-12s: timeout=%ds, mfa=%s, captcha_threshold=%d, rate_limit=%d/min\n",
        strtoupper($level),
        $p['session_timeout'],
        $p['mfa_enforced'] ? 'Yes' : 'No',
        $p['captcha_threshold'],
        $p['rate_limit']
    );
    echo "  password_policy: " . json_encode($p['password_policy']) . "\n";
}

// ── Skenario 5: User-based risk (with history) ──────────────────
echo "\n=== Skenario 5: User-Based Risk ===\n";
$user = new GenericUser([
    'id' => 1,
    'name' => 'Admin',
    'last_ip' => '10.0.0.1',
    'failed_attempts' => 3,
]);

$context = [
    'ip'              => '203.0.113.42',
    'last_ip'         => $user->last_ip,
    'ua'              => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0)',
    'recent_failures' => $user->failed_attempts,
];

$score = $riskEngine->calculateScore($user, $context);
echo "User: {$user->name}\n";
echo "Risk score: {$score}\n";
echo "Recommendation: " . ($score > 70 ? 'REQUIRE MFA' : 'Allow login') . "\n";

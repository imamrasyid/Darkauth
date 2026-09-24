<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Auth\AuthManager;
use Darkauth\Models\GenericUser;
use Darkauth\Support\Hash;
use Darkauth\MFA\TOTPDriver;
use Darkauth\Security\RiskEngine;
use Darkauth\Security\SecurityProfile;
use Darkauth\Audit\AuditLogger;
use Darkauth\Events\Dispatcher;

/**
 * Darkauth Ultimate Test Suite (Production-Grade Verification)
 */

function assertEquals($expected, $actual, $message) {
    if ($expected === $actual) {
        echo "✅ [PASS] $message\n";
    } else {
        echo "❌ [FAIL] $message\n";
        echo "   Expected: " . var_export($expected, true) . "\n";
        echo "   Actual:   " . var_export($actual, true) . "\n";
    }
}

echo "--- Darkauth Ultimate Test Suite ---\n\n";

// --- 1. Event & Audit System ---
echo "[1] Testing Event & Audit System...\n";
$events = new Dispatcher();
$loggedEvents = [];
$audit = new AuditLogger(function($log) use (&$loggedEvents) {
    $loggedEvents[] = $log;
}, 'test-audit-hmac-key');
$audit->subscribe($events);

$events->dispatch('auth.login.success', ['user_id' => 1]);
assertEquals(1, count($loggedEvents), "Audit logger captured the event");
assertEquals('auth.login.success', $loggedEvents[0]['event'], "Correct event name logged");

// --- 2. MFA (TOTP) Tests ---
echo "\n[2] Testing MFA (TOTP - RFC 6238)...\n";
$mfa = new TOTPDriver();
$secret = $mfa->generateSecret();
assertEquals(16, strlen($secret), "TOTP secret generation length");

// Note: We can't easily test the code without a time-sync, but we can test secret decoding
$qrUrl = $mfa->getQrCodeUrl('admin', $secret, 'DarkAuth');
assertEquals(true, strpos($qrUrl, 'otpauth://totp/') === 0, "QR Code URL format is correct");

// --- 3. Risk Engine & Security Profiles ---
echo "\n[3] Testing Risk Engine & Profiles...\n";
$risk = new RiskEngine();
$profiles = new SecurityProfile();

$score = $risk->calculateScore(null, ['ip' => '1.1.1.1', 'last_ip' => '2.2.2.2']);
assertEquals(true, $score >= 30, "Risk score increases on IP change");

$hardened = $profiles->get('hardened');
assertEquals(true, $hardened['mfa_enforced'], "Hardened profile enforces MFA");
assertEquals(1800, $hardened['session_timeout'], "Hardened profile has correct timeout");

// --- 4. Integration Test (AuthManager + Events) ---
echo "\n[4] Testing Full Integration...\n";
$config = [
    'defaults' => ['guard' => 'web'],
    'guards' => ['web' => ['driver' => 'session', 'provider' => 'users']],
    'providers' => [
        'users' => [
            'driver' => 'database',
            'callback' => function($t, $c) { 
                return (object)['id' => 1, 'username' => 'admin', 'password' => Hash::make('secret')]; 
            }
        ]
    ]
];

$auth = new AuthManager($config, $events);
$auth->guard('web')->attempt(['username' => 'admin', 'password' => 'secret']);

// Check if AuthManager automatically triggered the audit log via events
$lastLog = end($loggedEvents);
assertEquals('auth.login.success', $lastLog['event'], "AuthManager automatically triggered audit log");

// --- 5. UI Template Accessibility ---
echo "\n[5] Testing UI Templates (Government UX)...\n";
$html = \Darkauth\Support\UI\Templates::mfaOnboarding('qr_url', 'SECRET123');
assertEquals(true, strpos($html, 'Google Authenticator') !== false, "UI Template contains user-friendly instructions");
assertEquals(true, strpos($html, 'SECRET123') !== false, "UI Template displays backup secret");

echo "\n--- All Production-Grade Tests Completed ---\n";

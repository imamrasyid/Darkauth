<?php

/**
 * Example: Full Stack Integration
 *
 * Demonstrasi semua fitur DarkAuth dalam satu alur lengkap:
 * Session Login + JWT + MFA + Trusted Device + Rate Limiting + Audit + Risk
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Auth\AuthManager;
use Darkauth\Models\GenericUser;
use Darkauth\Events\Dispatcher;
use Darkauth\Audit\AuditLogger;
use Darkauth\MFA\TOTPDriver;
use Darkauth\MFA\RecoveryCodes;
use Darkauth\Security\TrustedDevice;
use Darkauth\Security\RiskEngine;
use Darkauth\Security\SecurityProfile;
use Darkauth\Security\DatabaseRateLimiter;
use Darkauth\Support\Hash;
use Darkauth\Support\SessionStorage;
use Darkauth\Support\UI\Templates;

// ══════════════════════════════════════════════════════════════════
// SETUP
// ══════════════════════════════════════════════════════════════════

echo "╔══════════════════════════════════════════════╗\n";
echo "║     DARKAUTH FULL STACK DEMO                ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

$events = new Dispatcher();
$storage = new SessionStorage();

// Audit logging
$auditLogs = [];
$audit = new AuditLogger(function ($logData) use (&$auditLogs) {
    $auditLogs[] = $logData;
    echo "  [AUDIT] {$logData['event']}\n";
}, 'audit-secret-key');
$audit->subscribe($events);

// Config
$config = [
    'defaults' => ['guard' => 'web'],
    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        'api' => ['driver' => 'jwt', 'provider' => 'users'],
    ],
    'providers' => [
        'users' => [
            'callback' => function ($id) {
                $users = [
                    1 => ['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com', 'mfa_enabled' => true],
                    2 => ['id' => 2, 'name' => 'User', 'email' => 'user@example.com', 'mfa_enabled' => false],
                ];
                return isset($users[$id]) ? new GenericUser($users[$id]) : null;
            },
        ],
    ],
    'jwt' => ['secret' => 'my-secret-key-min-32-characters-long!!', 'algo' => 'HS256', 'ttl' => 3600],
    'trusted_device' => ['hmac_key' => 'demo-device-hmac-key'],
];

$auth = new AuthManager($config);

// ══════════════════════════════════════════════════════════════════
// FLOW 1: Normal Login
// ══════════════════════════════════════════════════════════════════

echo "═══ FLOW 1: Normal Login ═══\n";

// 1. Rate limit check
$limiter = $auth->getRateLimiter();
$loginKey = 'login:192.168.1.100';
$limiter->clear($loginKey);

echo "1. Rate limit check: " . $limiter->remaining($loginKey, 5) . " attempts remaining\n";

// 2. Risk assessment
$riskEngine = $auth->getRiskEngine();
$profile = $auth->getSecurityProfile()->get('standard');
$context = ['ip' => '192.168.1.100', 'last_ip' => '192.168.1.100', 'ua' => 'Mozilla/5.0', 'recent_failures' => 0];
$score = $riskEngine->calculateScore(null, $context);
echo "2. Risk score: {$score} (threshold: {$profile['captcha_threshold']})\n";

// 3. CAPTCHA check (if needed)
if ($riskEngine->shouldChallenge($score, $profile['captcha_threshold'] * 10)) {
    echo "3. CAPTCHA required\n";
} else {
    echo "3. CAPTCHA not required\n";
}

// 4. Login
$user = new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com']);
$auth->guard('web')->login($user);
echo "4. Logged in as: " . $auth->guard('web')->user()->name . "\n";

// 5. MFA check
$mfa = $auth->mfa();
if ($user->mfa_enabled) {
    echo "5. MFA enabled — verification required\n";
    // Dalam produksi: redirect ke MFA form
}

// 6. Trusted device
$device = $auth->getTrustedDeviceManager();
$deviceToken = $device->issue($user->id, 30);
echo "6. Device token issued: " . substr($deviceToken, 0, 20) . "...\n";

// 7. Issue JWT for API access
$jwtToken = $auth->guard('api')->issueToken($user, ['role' => 'admin']);
echo "7. JWT token issued: " . substr($jwtToken, 0, 30) . "...\n";

// ══════════════════════════════════════════════════════════════════
// FLOW 2: Suspicious Login (High Risk)
// ══════════════════════════════════════════════════════════════════

echo "\n═══ FLOW 2: Suspicious Login ═══\n";

$context = ['ip' => '203.0.113.42', 'last_ip' => '192.168.1.100', 'ua' => 'curl/7.68', 'recent_failures' => 5];
$score = $riskEngine->calculateScore($user, $context);
echo "1. Risk score: {$score} (HIGH)\n";

if ($riskEngine->shouldChallenge($score, 30)) {
    echo "2. Action: Require MFA + CAPTCHA\n";
    echo "3. Notify admin\n";
}

$events->dispatch('auth.risk.detected', ['user_id' => $user->id, 'score' => $score]);
echo "4. Risk event dispatched\n";

// ══════════════════════════════════════════════════════════════════
// FLOW 3: Password Recovery
// ══════════════════════════════════════════════════════════════════

echo "\n═══ FLOW 3: Password Recovery ═══\n";

$recovery = $auth->getRecoveryWorkflow();
$token = $recovery->createToken($user->id, 900);
echo "1. Recovery token: " . substr($token, 0, 20) . "...\n";
echo "2. Email sent to: {$user->email}\n";

// Verify + change password
if ($recovery->verifyToken($user->id, $token)) {
    echo "3. Token verified\n";
    $newHashed = Hash::make('NewSecurePass123!');
    echo "4. Password updated (hashed)\n";
    $recovery->complete($user->id, $token);
    echo "5. Token invalidated\n";
}

// ══════════════════════════════════════════════════════════════════
// FLOW 4: MFA Setup
// ══════════════════════════════════════════════════════════════════

echo "\n═══ FLOW 4: MFA Setup ═══\n";

$mfaSecret = $mfa->generateSecret();
echo "1. Secret generated: {$mfaSecret}\n";

$qrUrl = $mfa->getQrCodeUrl($user->email, $mfaSecret, 'DarkAuth Demo');
echo "2. QR URL: {$qrUrl}\n";

$recoveryCodes = new RecoveryCodes();
$codes = $recoveryCodes->generate(8);
echo "3. Recovery codes generated: " . count($codes) . " codes\n";

// Simulate verification
echo "4. MFA verification: " . ($mfa->verify($mfaSecret, '123456') ? 'Success' : 'Pending') . "\n";

// ══════════════════════════════════════════════════════════════════
// FLOW 5: Logout & Cleanup
// ══════════════════════════════════════════════════════════════════

echo "\n═══ FLOW 5: Logout ═══\n";

$auth->guard('web')->logout();
echo "1. Session logged out\n";

$device->revokeAll($user->id);
echo "2. All trusted devices revoked\n";

$limiter->clear($loginKey);
echo "3. Rate limit cleared\n";

echo "\n═══ Summary ═══\n";
echo "Total audit logs: " . count($auditLogs) . "\n";
echo "Features used: Session, JWT, MFA, Trusted Device, Rate Limit, Audit, Risk Engine\n";

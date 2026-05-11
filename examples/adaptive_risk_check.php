<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Security\RiskEngine;
use Darkauth\Security\SecurityProfile;

/**
 * Example: Adaptive Security & Risk-Based Challenges
 */

$riskEngine = new RiskEngine();
$profiles = new SecurityProfile();

// 1. Load security profile (e.g., from config)
$currentProfile = $profiles->get('standard');

// 2. Simulation context (Current login attempt)
$context = [
    'ip' => '192.168.1.100',
    'last_ip' => '10.0.0.5', // Simulation: User IP changed!
    'ua' => 'Mozilla/5.0...',
    'recent_failures' => 4   // Simulation: Multiple failed attempts detected
];

// 3. Calculate Risk
$riskScore = $riskEngine->calculateScore(null, $context);
echo "Risk Score detected: " . $riskScore . "%\n";

// 4. Decide challenge based on profile
if ($riskEngine->shouldChallenge($riskScore, $currentProfile['captcha_threshold'] * 10)) {
    echo "⚠️ HIGH RISK: Triggering CAPTCHA Challenge...\n";
    // Here you would render ReCaptchaDriver
}

if ($currentProfile['mfa_enforced'] || $riskScore > 70) {
    echo "🚨 CRITICAL RISK: MFA Verification Required!\n";
} else {
    echo "✅ RISK ACCEPTABLE: Proceeding with normal login.\n";
}

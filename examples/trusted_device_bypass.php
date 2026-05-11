<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Security\TrustedDevice;
use Darkauth\Support\SessionStorage;

/**
 * Example: Trusted Device (MFA Bypass)
 */

$storage = new SessionStorage();
$deviceManager = new TrustedDevice($storage);

$userId = 1;

// 1. User logs in with MFA and checks "Remember this device"
// We issue a token that lasts for 30 days
$deviceToken = $deviceManager->issue($userId, 30);
echo "New Trusted Device Token issued: " . $deviceToken . "\n";

// 2. Simulation: User returns next week
// The token is usually stored in a secure cookie
$returningToken = $deviceToken; // Simulation

if ($deviceManager->verify($userId, $returningToken)) {
    echo "✅ Device is TRUSTED. Bypassing MFA for user #$userId.\n";
} else {
    echo "⚠️ Device is NOT TRUSTED or Token Expired. MFA Required!\n";
}

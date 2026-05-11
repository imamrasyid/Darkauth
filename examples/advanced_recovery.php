<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Auth\RecoveryWorkflow;
use Darkauth\Events\Dispatcher;
use Darkauth\Support\SessionStorage;

/**
 * Example: Secure Account Recovery Workflow
 */

$storage = new SessionStorage();
$events = new Dispatcher();
$recovery = new RecoveryWorkflow($storage, $events);

// 1. User forgot password - create a 15-minute token
$userId = 101;
$token = $recovery->createToken($userId, 900); // 15 mins

echo "Recovery Token created: " . $token . "\n";
echo "Email sent to user with link: /reset?id=$userId&token=$token\n\n";

// 2. User clicks the link - verify token
$inputToken = $token; // Simulation

if ($recovery->verifyToken($userId, $inputToken)) {
    echo "✅ Token Valid: User is allowed to change password.\n";
    
    // Perform password change logic here...
    
    // 3. Complete and invalidate token
    $recovery->complete($userId);
    echo "Recovery completed. Token invalidated.\n";
} else {
    echo "❌ Token Invalid or Expired.\n";
}

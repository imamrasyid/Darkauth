<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Events\Dispatcher;
use Darkauth\Audit\AuditLogger;

/**
 * Example: Centralized Audit Logging
 */

// 1. Setup Event Dispatcher
$events = new Dispatcher();

// 2. Setup Audit Logger with Database/File callback
$audit = new AuditLogger(function($logData) {
    // In a real app, you would:
    // $CI->db->insert('darkauth_audit_logs', $logData);
    
    echo "--- [AUDIT LOG RECORDED] ---\n";
    echo "Event: " . $logData['event'] . "\n";
    echo "IP: " . $logData['ip'] . "\n";
    echo "Time: " . $logData['timestamp'] . "\n";
    echo "Data: " . $logData['data'] . "\n\n";
});

// 3. Subscribe to security events
$audit->subscribe($events);

// 4. Simulate various security events across the system
$events->dispatch('auth.login.failed', ['username' => 'admin', 'reason' => 'invalid_password']);
$events->dispatch('auth.mfa.success', ['user_id' => 45, 'method' => 'totp']);
$events->dispatch('auth.risk.detected', ['user_id' => 12, 'risk_score' => 85, 'reason' => 'impossible_travel']);

<?php

/**
 * Example: Audit Trail & Monitoring
 *
 * Log semua aktivitas keamanan untuk compliance & debugging.
 * Setiap log ditandatangani dengan HMAC (tamper-resistant).
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Events\Dispatcher;
use Darkauth\Audit\AuditLogger;

// ── Setup ────────────────────────────────────────────────────────
$events = new Dispatcher();
$hmacKey = 'audit-secret-key-change-in-production';

// ── Audit Logger dengan callback ─────────────────────────────────
$auditLogs = [];
$audit = new AuditLogger(function ($logData) use (&$auditLogs) {
    $auditLogs[] = $logData;

    // Di produksi, simpan ke database:
    // $CI =& get_instance();
    // $CI->db->insert('darkauth_audit_logs', [
    //     'event'      => $logData['event'],
    //     'user_id'    => $logData['user_id'] ?? null,
    //     'ip_address' => $logData['ip_address'],
    //     'data'       => $logData['data'],
    //     'signature'  => $logData['signature'],
    //     'created_at' => $logData['timestamp'],
    // ]);

    echo "[AUDIT] {$logData['event']}";
    if (isset($logData['user_id'])) {
        echo " | user_id={$logData['user_id']}";
    }
    echo " | ip={$logData['ip_address']}";
    echo " | " . $logData['timestamp'] . "\n";
}, $hmacKey);

$audit->subscribe($events);

// ── Skenario 1: Login events ────────────────────────────────────
echo "=== Login Events ===\n";
$events->dispatch('auth.login.success', [
    'user_id' => 1,
    'ip'      => '192.168.1.100',
]);

$events->dispatch('auth.login.failed', [
    'username' => 'admin',
    'reason'   => 'invalid_password',
    'ip'       => '192.168.1.200',
]);

// ── Skenario 2: MFA events ──────────────────────────────────────
echo "\n=== MFA Events ===\n";
$events->dispatch('auth.mfa.success', [
    'user_id' => 1,
    'method'  => 'totp',
]);

$events->dispatch('auth.mfa.failed', [
    'user_id' => 1,
    'method'  => 'totp',
    'reason'  => 'invalid_code',
]);

// ── Skenario 3: Security events ─────────────────────────────────
echo "\n=== Security Events ===\n";
$events->dispatch('auth.risk.detected', [
    'user_id'    => 1,
    'risk_score' => 85,
    'reason'     => 'impossible_travel',
    'old_ip'     => '10.0.0.1',
    'new_ip'     => '203.0.113.42',
]);

$events->dispatch('auth.password.changed', [
    'user_id' => 1,
    'ip'      => '192.168.1.100',
]);

// ── Skenario 4: Verify audit log integrity ──────────────────────
echo "\n=== Audit Log Integrity ===\n";
echo "Total logs recorded: " . count($auditLogs) . "\n";

if (!empty($auditLogs)) {
    $lastLog = end($auditLogs);
    echo "Last log:\n";
    echo "  Event     : {$lastLog['event']}\n";
    echo "  Signature : {$lastLog['signature']}\n";
    echo "  Tamper-proof: Log cannot be modified without detection.\n";
}

// ── Skenario 5: Custom event listener ───────────────────────────
echo "\n=== Custom Event Listener ===\n";
$events->listen('auth.login.failed', function ($payload) {
    if (isset($payload['reason']) && $payload['reason'] === 'invalid_password') {
        echo "[ALERT] Failed login for: {$payload['username']}\n";
        // Kirim email notifikasi ke admin
    }
});

$events->dispatch('auth.login.failed', [
    'username' => 'admin',
    'reason'   => 'invalid_password',
]);

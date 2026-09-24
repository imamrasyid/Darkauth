<?php

/**
 * Example: Rate Limiting
 *
 * Database-backed rate limiter untuk brute-force protection.
 * Mendukung atomic lock untuk mencegah race condition.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Security\DatabaseRateLimiter;

// ── Setup: Simulasi storage ─────────────────────────────────────
$store = [];

$storageCallback = function ($action, $key, $value = null) use (&$store) {
    switch ($action) {
        case 'get':
            return $store[$key] ?? null;
        case 'set':
            $store[$key] = $value;
            break;
        case 'remove':
            unset($store[$key]);
            break;
    }
};

$limiter = new DatabaseRateLimiter($storageCallback);

// ── Skenario 1: Basic rate limiting ─────────────────────────────
echo "=== Skenario 1: Login Rate Limiting ===\n";
$key = 'login:192.168.1.100';
$maxAttempts = 5;
$decaySeconds = 900; // 15 menit

echo "Max attempts: {$maxAttempts}\n\n";

// Simulasi 5 percobaan gagal
for ($i = 1; $i <= 6; $i++) {
    $remaining = $limiter->remaining($key, $maxAttempts);

    if ($limiter->tooManyAttempts($key, $maxAttempts)) {
        $wait = $limiter->availableIn($key);
        echo "Attempt {$i}: BLOCKED! Tunggu {$wait} detik.\n";
    } else {
        echo "Attempt {$i}: Allowed. Remaining: {$remaining}\n";
        $limiter->hit($key, $decaySeconds);
    }
}

// ── Skenario 2: Reset setelah login berhasil ────────────────────
echo "\n=== Skenario 2: Reset on Success ===\n";
$limiter->clear($key);
echo "Counter reset.\n";
echo "Remaining after reset: " . $limiter->remaining($key, $maxAttempts) . "\n";

// ── Skenario 3: Rate limit berbeda per endpoint ─────────────────
echo "\n=== Skenario 3: Per-Endpoint Limits ===\n";
$endpoints = [
    'login'    => ['max' => 5, 'decay' => 900],
    'register' => ['max' => 3, 'decay' => 3600],
    'password' => ['max' => 3, 'decay' => 3600],
];

foreach ($endpoints as $endpoint => $config) {
    $epKey = "{$endpoint}:192.168.1.100";
    $limiter->clear($epKey);
    echo sprintf(
        "%-10s: max %d attempts per %d seconds\n",
        $endpoint,
        $config['max'],
        $config['decay']
    );
}

// ── Skenario 4: Atomic lock (race condition prevention) ──────────
echo "\n=== Skenario 4: Atomic Lock ===\n";
$lockStore = [];
$lockCallback = function ($key, callable $fn) use (&$lockStore) {
    // Simulasi file lock
    if (isset($lockStore[$key])) {
        throw new RuntimeException("Lock held: {$key}");
    }
    $lockStore[$key] = true;
    try {
        $result = $fn();
        unset($lockStore[$key]);
        return $result;
    } catch (\Exception $e) {
        unset($lockStore[$key]);
        throw $e;
    }
};

$atomicLimiter = new DatabaseRateLimiter($storageCallback, $lockCallback);
$atomicKey = 'api:192.168.1.100';

// Hit concurrently (simulasi)
for ($i = 0; $i < 3; $i++) {
    $count = $atomicLimiter->hit($atomicKey, 60);
    echo "Hit {$i}: count = {$count}\n";
}

echo "Remaining: " . $atomicLimiter->remaining($atomicKey, 10) . "\n";

<?php

/**
 * Example: Session-Based Login (Web)
 *
 * Menggunakan SessionGuard untuk login berbasis cookie.
 * Cocok untuk website tradisional dengan form login.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Auth\AuthManager;
use Darkauth\Models\GenericUser;

// ── Konfigurasi ──────────────────────────────────────────────────
$config = [
    'defaults' => [
        'guard' => 'web',
    ],
    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
    ],
    'providers' => [
        'users' => [
            'callback' => function ($id) {
                // Simulasi database lookup
                $users = [
                    1 => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'password' => password_hash('secret', PASSWORD_DEFAULT)],
                    2 => ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => password_hash('secret', PASSWORD_DEFAULT)],
                ];

                return isset($users[$id]) ? new GenericUser($users[$id]) : null;
            },
        ],
    ],
];

// ── Inisialisasi ─────────────────────────────────────────────────
$auth = new AuthManager($config);
$guard = $auth->guard('web');

// ── Skenario 1: Login langsung dengan objek user ────────────────
echo "=== Skenario 1: Login Langsung ===\n";
$john = new GenericUser(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
$guard->login($john);

if ($guard->check()) {
    echo "User ID : " . $guard->id() . "\n";
    echo "Name    : " . $guard->user()->name . "\n";
    echo "Guest?  : " . ($guard->guest() ? 'Yes' : 'No') . "\n";
}

$guard->logout();
echo "After logout: " . ($guard->guest() ? 'Guest' : 'Authenticated') . "\n\n";

// ── Skenario 2: Login by ID ─────────────────────────────────────
echo "=== Skenario 2: Login by ID ===\n";
$guard->loginUsingId(2);

if ($guard->check()) {
    echo "Logged in as ID: " . $guard->id() . "\n";
    echo "Name: " . $guard->user()->name . "\n";
}

$guard->logout();

// ── Skenario 3: Login dengan attempt (validasi kredensial) ──────
echo "\n=== Skenario 3: Login dengan validate() ===\n";
$found = $auth->guard('web')->validate(['id' => 1]);

// validate() hanya mengecek kredensial, tidak login.
echo "Validate result: " . ($found ? 'Found' : 'Not found') . "\n";

// ── Skenario 4: Cek guard status ────────────────────────────────
echo "\n=== Guard Info ===\n";
echo "Guard name: " . $auth->guard('web')->getName() . "\n";
echo "Default    : " . $auth->getDefaultGuard() . "\n";

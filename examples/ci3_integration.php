<?php

/**
 * Example: CodeIgniter 3 Integration
 *
 * Panduan lengkap integrasi DarkAuth ke CI3:
 * 1. Composer autoload
 * 2. Config file
 * 3. Controller usage
 * 4. Middleware usage
 */

require_once __DIR__ . '/../vendor/autoload.php';

// ══════════════════════════════════════════════════════════════════
// BAGIAN 1: Konfigurasi
// ══════════════════════════════════════════════════════════════════

/**
 * File: application/config/config.php
 * Tambahkan di bagian bawah:
 *
 * $config['composer_autoload'] = TRUE;
 */

/**
 * File: application/config/auth.php (copy dari config/auth.php)
 * Sesuaikan:
 */

/*
return [
    'defaults' => ['guard' => 'web'],
    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        'api' => ['driver' => 'jwt',     'provider' => 'users'],
    ],
    'providers' => [
        'users' => [
            'driver'   => 'database',
            'table'    => 'users',
            'callback' => function ($id) {
                $CI =& get_instance();
                $row = $CI->db->get_where('users', ['id' => $id])->row();
                return $row ? new \Darkauth\Models\GenericUser((array) $row) : null;
            },
        ],
    ],
    'jwt' => [
        'secret' => env('JWT_SECRET', 'change-me-to-a-secure-key'),
        'algo'   => 'HS256',
        'ttl'    => 3600,
        'issuer' => 'myapp',
    ],
];
*/

// ══════════════════════════════════════════════════════════════════
// BAGIAN 2: Controller Example (CI3)
// ══════════════════════════════════════════════════════════════════

/**
 * File: application/controllers/Auth.php
 *
 * class Auth extends CI_Controller {
 *     public function __construct() {
 *         parent::__construct();
 *         $this->load->library(\Darkauth\Support\CI3Auth::class, null, 'auth');
 *     }
 *
 *     // ... methods di bawah
 * }
 */

echo "=== CI3 Integration Guide ===\n\n";

// ── Demo: Standalone usage ──────────────────────────────────────
use Darkauth\Auth\AuthManager;
use Darkauth\Models\GenericUser;
use Darkauth\Middleware\Authenticate;

$config = [
    'defaults' => ['guard' => 'web'],
    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        'api' => ['driver' => 'jwt', 'provider' => 'users'],
    ],
    'providers' => [
        'users' => [
            'callback' => function ($id) {
                $users = [1 => ['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com']];
                return isset($users[$id]) ? new GenericUser($users[$id]) : null;
            },
        ],
    ],
    'jwt' => [
        'secret' => 'my-secret-key-min-32-characters-long!!',
        'algo'   => 'HS256',
        'ttl'    => 3600,
    ],
];

$auth = new AuthManager($config);
$web = $auth->guard('web');

// ── Login ────────────────────────────────────────────────────────
echo "1. Login dengan objek user:\n";
$user = new GenericUser(['id' => 1, 'name' => 'Admin']);
$web->login($user);
echo "   Logged in as: " . $web->user()->name . "\n";

// ── Check ────────────────────────────────────────────────────────
echo "\n2. Cek status autentikasi:\n";
echo "   check(): " . ($web->check() ? 'Yes' : 'No') . "\n";
echo "   guest(): " . ($web->guest() ? 'Yes' : 'No') . "\n";
echo "   id()   : " . $web->id() . "\n";

// ── Middleware ────────────────────────────────────────────────────
echo "\n3. Middleware (Authenticate):\n";
$middleware = new Authenticate($auth);
try {
    $middleware->handle('web');
    echo "   Middleware passed!\n";
} catch (\Exception $e) {
    echo "   Middleware rejected: " . $e->getMessage() . "\n";
}

// ── JWT Token ────────────────────────────────────────────────────
echo "\n4. Issue JWT token:\n";
$token = $auth->guard('api')->issueToken($user);
echo "   Token: " . substr($token, 0, 40) . "...\n";

// ── Logout ───────────────────────────────────────────────────────
echo "\n5. Logout:\n";
$web->logout();
echo "   guest(): " . ($web->guest() ? 'Yes' : 'No') . "\n";

// ══════════════════════════════════════════════════════════════════
// BAGIAN 3: View Templates
// ══════════════════════════════════════════════════════════════════

echo "\n=== View Integration ===\n";
echo "Gunakan Templates untuk render UI:\n";
echo "  - Darkauth\Support\UI\Templates::styles()           → CSS\n";
echo "  - Darkauth\Support\UI\Templates::mfaOnboarding(...)  → MFA setup form\n";
echo "  - Darkauth\Support\UI\Templates::recoveryCodes(...)  → Recovery codes list\n";

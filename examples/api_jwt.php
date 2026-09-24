<?php

/**
 * Example: JWT API Authentication
 *
 * Menggunakan JWTGuard untuk REST API stateless.
 * Token dikirim via header: Authorization: Bearer <token>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Auth\AuthManager;
use Darkauth\Models\GenericUser;

// ── Konfigurasi ──────────────────────────────────────────────────
$config = [
    'defaults' => [
        'guard' => 'api',
    ],
    'guards' => [
        'api' => [
            'driver'   => 'jwt',
            'provider' => 'users',
        ],
    ],
    'providers' => [
        'users' => [
            'callback' => function ($id) {
                $users = [
                    1 => ['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'admin'],
                    2 => ['id' => 2, 'name' => 'User', 'email' => 'user@example.com', 'role' => 'user'],
                ];

                return isset($users[$id]) ? new GenericUser($users[$id]) : null;
            },
        ],
    ],
    'jwt' => [
        'secret' => 'my-secret-key-min-32-characters-long!!',
        'algo'   => 'HS256',
        'ttl'    => 3600,
        'issuer' => 'myapp',
    ],
];

$auth = new AuthManager($config);

// ── Skenario 1: Issue token ─────────────────────────────────────
echo "=== Skenario 1: Issue JWT Token ===\n";
$guard = $auth->guard('api');
$user = new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'admin']);

$token = $guard->issueToken($user, [
    'role' => 'admin',
    'permissions' => ['read', 'write'],
]);

echo "Token (truncated): " . substr($token, 0, 50) . "...\n";
echo "Token length: " . strlen($token) . " bytes\n\n";

// ── Skenario 2: Validasi token ──────────────────────────────────
echo "=== Skenario 2: Validasi Token ===\n";
$guard->setToken($token);

if ($guard->check()) {
    $user = $guard->user();
    echo "Authenticated: Yes\n";
    echo "User ID: " . $guard->id() . "\n";
    echo "Name   : " . $user->name . "\n";
    echo "Role   : " . $user->role . "\n";
} else {
    echo "Authenticated: No\n";
}

// ── Skenario 3: Invalid token ───────────────────────────────────
echo "\n=== Skenario 3: Invalid Token ===\n";
$invalidGuard = $auth->guard('api');
$invalidGuard->setToken('invalid.token.here');

if (!$invalidGuard->check()) {
    echo "Correctly rejected invalid token.\n";
    echo "User: " . var_export($invalidGuard->user(), true) . "\n";
}

// ── Skenario 4: JWT dengan custom claims ────────────────────────
echo "\n=== Skenario 4: Custom Claims ===\n";
$user2 = new GenericUser(['id' => 2, 'name' => 'User', 'email' => 'user@example.com']);
$customToken = $guard->issueToken($user2, [
    'role'     => 'user',
    'department' => 'engineering',
    'level'    => 3,
]);

$decoded = (new \Darkauth\Support\JwtHelper(
    $config['jwt']['secret'],
    $config['jwt']['algo'],
    $config['jwt']['issuer']
))->decode($customToken);

echo "Custom claims in token:\n";
echo "  role       : " . ($decoded['role'] ?? 'N/A') . "\n";
echo "  department : " . ($decoded['department'] ?? 'N/A') . "\n";
echo "  level      : " . ($decoded['level'] ?? 'N/A') . "\n";
echo "  iss        : " . ($decoded['iss'] ?? 'N/A') . "\n";

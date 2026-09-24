<?php

/**
 * Example: Custom Guard Driver
 *
 * Tambah driver auth sendiri via AuthManager::extend().
 * Contoh: LDAP, OAuth, API key, dll.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Auth\AuthManager;
use Darkauth\Core\GuardInterface;
use Darkauth\Core\UserInterface;
use Darkauth\Models\GenericUser;

// ══════════════════════════════════════════════════════════════════
// Custom Guard: API Key Authentication
// ══════════════════════════════════════════════════════════════════

class ApiKeyGuard implements GuardInterface
{
    protected $validKeys;
    protected $user;
    protected $name = 'apikey';

    public function __construct(array $validKeys)
    {
        $this->validKeys = $validKeys;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?UserInterface
    {
        return $this->user;
    }

    public function id()
    {
        return $this->user ? $this->user->getAuthIdentifier() : null;
    }

    public function validate(array $credentials = []): bool
    {
        $apiKey = $credentials['api_key'] ?? '';

        if (isset($this->validKeys[$apiKey])) {
            $userData = $this->validKeys[$apiKey];
            $this->user = new GenericUser($userData);
            return true;
        }

        return false;
    }

    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }

    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        return $this->validate($credentials);
    }

    public function getName(): string
    {
        return $this->name;
    }
}

// ══════════════════════════════════════════════════════════════════
// Demo
// ══════════════════════════════════════════════════════════════════

echo "=== Custom Guard Driver Demo ===\n\n";

// Daftar API keys yang valid
$validKeys = [
    'sk-abc123def456' => ['id' => 1, 'name' => 'Mobile App', 'type' => 'app'],
    'sk-xyz789ghi012' => ['id' => 2, 'name' => 'Partner API', 'type' => 'partner'],
];

$config = [
    'defaults' => ['guard' => 'api_key'],
    'guards' => [
        'api_key' => [
            'driver' => 'apikey',
        ],
    ],
    'providers' => [
        'users' => [
            'callback' => function ($id) {
                return null;
            },
        ],
    ],
];

$auth = new AuthManager($config);

// ── Register custom driver ──────────────────────────────────────
$auth->extend('apikey', function ($app, $name, $config) use ($validKeys) {
    return new ApiKeyGuard($validKeys);
});

// ── Skenario 1: Valid API key ────────────────────────────────────
echo "1. Valid API Key:\n";
$guard = $auth->guard('api_key');
$guard->validate(['api_key' => 'sk-abc123def456']);

if ($guard->check()) {
    echo "   Authenticated: " . $guard->user()->name . "\n";
    echo "   Type: " . $guard->user()->type . "\n";
    echo "   ID: " . $guard->id() . "\n";
}

// ── Skenario 2: Invalid API key ──────────────────────────────────
echo "\n2. Invalid API Key:\n";
$guard2 = $auth->guard('api_key');
$valid = $guard2->validate(['api_key' => 'sk-invalid-key']);

echo "   Valid: " . ($valid ? 'Yes' : 'No') . "\n";
echo "   User: " . var_export($guard2->user(), true) . "\n";

// ── Skenario 3: Guest check ─────────────────────────────────────
echo "\n3. Guest Check:\n";
$guestGuard = $auth->guard('api_key');
echo "   Guest: " . ($guestGuard->guest() ? 'Yes' : 'No') . "\n";

echo "\n=== Custom Guard Pattern ===\n";
echo "1. Implement GuardInterface\n";
echo "2. Register via AuthManager::extend('name', callback)\n";
echo "3. Use via AuthManager::guard('name')\n";
echo "4. Callback receives: (\$app, \$name, \$config)\n";

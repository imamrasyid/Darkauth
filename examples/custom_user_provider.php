<?php

/**
 * Example: Custom User Provider
 *
 * Implement UserProviderInterface untuk ORM sendiri
 * (Eloquent, Doctrine, RedBeanPHP, atau database manual).
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Core\UserProviderInterface;
use Darkauth\Core\UserInterface;
use Darkauth\Models\GenericUser;
use Darkauth\Auth\AuthManager;
use Darkauth\Support\Hash;

// ══════════════════════════════════════════════════════════════════
// Custom User Provider
// ══════════════════════════════════════════════════════════════════

class CustomUserProvider implements UserProviderInterface
{
    protected $table;
    protected $pdo;

    public function __construct($pdo, string $table = 'users')
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function retrieveById($identifier): ?UserInterface
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$identifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new GenericUser($row) : null;
    }

    public function retrieveByCredentials(array $credentials): ?UserInterface
    {
        $conditions = [];
        $params = [];

        foreach ($credentials as $key => $value) {
            if ($key !== 'password') {
                $conditions[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (empty($conditions)) {
            return null;
        }

        $where = implode(' AND ', $conditions);
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$where} LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new GenericUser($row) : null;
    }

    public function validateCredentials(UserInterface $user, array $credentials): bool
    {
        $password = $credentials['password'] ?? '';
        $hashedPassword = $user->getAuthPassword();

        return Hash::check($password, $hashedPassword);
    }
}

// ══════════════════════════════════════════════════════════════════
// Demo: Menggunakan Custom Provider
// ══════════════════════════════════════════════════════════════════

echo "=== Custom User Provider Demo ===\n\n";

// Simulasi database connection
try {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buat tabel
    $pdo->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        email TEXT NOT NULL,
        password TEXT NOT NULL
    )");

    // Insert sample user
    $hashedPassword = Hash::make('secret123');
    $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)")
        ->execute(['admin', 'admin@example.com', $hashedPassword]);

    echo "Database ready (SQLite in-memory)\n\n";

    // ── Register provider ke AuthManager ─────────────────────────
    $provider = new CustomUserProvider($pdo, 'users');

    $config = [
        'defaults' => ['guard' => 'web'],
        'guards' => [
            'web' => ['driver' => 'session', 'provider' => 'users'],
        ],
        'providers' => [
            'users' => [
                'callback' => function ($id) use ($provider) {
                    return $provider->retrieveById($id);
                },
            ],
        ],
    ];

    $auth = new AuthManager($config);

    // ── Lookup user ──────────────────────────────────────────────
    echo "1. Retrieve by ID:\n";
    $user = $provider->retrieveById(1);
    echo "   Found: {$user->username} ({$user->email})\n";

    echo "\n2. Retrieve by credentials:\n";
    $user = $provider->retrieveByCredentials(['username' => 'admin']);
    echo "   Found: {$user->username}\n";

    echo "\n3. Validate credentials:\n";
    $valid = $provider->validateCredentials($user, ['password' => 'secret123']);
    echo "   Password valid: " . ($valid ? 'Yes' : 'No') . "\n";

    $invalid = $provider->validateCredentials($user, ['password' => 'wrong']);
    echo "   Wrong password: " . ($invalid ? 'Yes' : 'No') . "\n";

    // ── Login dengan provider ────────────────────────────────────
    echo "\n4. Login via AuthManager:\n";
    $auth->guard('web')->login($user);
    echo "   Logged in as: " . $auth->guard('web')->user()->username . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

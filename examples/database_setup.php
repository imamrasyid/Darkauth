<?php

/**
 * Example: Database Setup & Integration
 *
 * Setup tabel database untuk DarkAuth fitur lanjut.
 * Termasuk: users, MFA, trusted devices, audit logs, rate limiting.
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== Database Setup Guide ===\n\n";

// ══════════════════════════════════════════════════════════════════
// SKEMA DATABASE
// ══════════════════════════════════════════════════════════════════

$sql = <<<'SQL'
-- 1. Tabel Users (minimal)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(64) NOT NULL UNIQUE,
    `email` VARCHAR(128) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `mfa_secret` VARCHAR(64) DEFAULT NULL,
    `mfa_enabled` TINYINT(1) DEFAULT 0,
    `remember_token` VARCHAR(100) DEFAULT NULL,
    `last_ip` VARCHAR(45) DEFAULT NULL,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. MFA Recovery Codes
CREATE TABLE IF NOT EXISTS `darkauth_mfa_secrets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `secret` VARCHAR(64) NOT NULL,
    `recovery_codes` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Trusted Devices
CREATE TABLE IF NOT EXISTS `darkauth_trusted_devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(128) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `last_ip` VARCHAR(45) DEFAULT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Audit Logs
CREATE TABLE IF NOT EXISTS `darkauth_audit_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `event` VARCHAR(64) NOT NULL,
    `user_id` INT DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `data` JSON DEFAULT NULL,
    `signature` VARCHAR(64) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_event` (`event`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Rate Limiting (optional, bisa pakai Redis)
CREATE TABLE IF NOT EXISTS `darkauth_rate_limits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(128) NOT NULL UNIQUE,
    `attempts` INT DEFAULT 0,
    `last_attempt_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

echo "SQL Schema:\n";
echo str_repeat('─', 60) . "\n";
echo $sql . "\n";
echo str_repeat('─', 60) . "\n\n";

// ══════════════════════════════════════════════════════════════════
// MYSQL SETUP VIA PDO (Demo)
// ══════════════════════════════════════════════════════════════════

echo "=== SQLite Demo (In-Memory) ===\n\n";

try {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buat tabel users
    $pdo->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        mfa_secret TEXT,
        mfa_enabled INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Tabel 'users' berhasil dibuat.\n\n";

    // Insert sample data
    $hashedPassword = \Darkauth\Support\Hash::make('password123');
    $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)")
        ->execute(['admin', 'admin@example.com', $hashedPassword]);

    $pdo->prepare("INSERT INTO users (username, email, password, mfa_enabled) VALUES (?, ?, ?, ?)")
        ->execute(['john', 'john@example.com', $hashedPassword, 1]);

    echo "Sample data inserted:\n";
    $stmt = $pdo->query("SELECT id, username, email, mfa_enabled FROM users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  [{$row['id']}] {$row['username']} ({$row['email']})" .
             ($row['mfa_enabled'] ? ' [MFA]' : '') . "\n";
    }

    echo "\n=== Custom UserProvider Integration ===\n";
    echo "
//gunakan PDO connection dengan DarkAuth:

\$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'user', 'pass');

\$config['providers']['users']['callback'] = function (\$id) use (\$pdo) {
    \$stmt = \$pdo->prepare('SELECT * FROM users WHERE id = ?');
    \$stmt->execute([\$id]);
    \$row = \$stmt->fetch(PDO::FETCH_ASSOC);
    return \$row ? new GenericUser(\$row) : null;
};

\$config['providers']['users']['callback'] = function (\$credentials) use (\$pdo) {
    \$stmt = \$pdo->prepare('SELECT * FROM users WHERE username = ?');
    \$stmt->execute([\$credentials['username']]);
    \$row = \$stmt->fetch(PDO::FETCH_ASSOC);
    return \$row ? new GenericUser(\$row) : null;
};
";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Notes ===\n";
echo "1. Untuk MySQL/MariaDB: gunakan database.sql yang disertakan\n";
echo "2. Untuk PostgreSQL: ubah AUTO_INCREMENT ke SERIAL\n";
echo "3. Untuk SQLite: ubah TIMESTAMP ke DATETIME\n";
echo "4. Selalu gunakan prepared statements\n";
echo "5. Index di user_id & token_hash penting untuk performance\n";

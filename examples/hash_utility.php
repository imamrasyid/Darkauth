<?php

/**
 * Example: Hash Utility
 *
 * Password hashing, random token generation, timing-safe compare.
 * Semua menggunakan bcrypt (default) atau custom algorithm.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Support\Hash;

echo "=== Hash Utility Demo ===\n\n";

// ── 1. Password Hashing ─────────────────────────────────────────
echo "1. Password Hashing (bcrypt)\n";
$password = 'MySecurePassword123!';
$hashed = Hash::make($password);
echo "   Original: {$password}\n";
echo "   Hashed  : {$hashed}\n";
echo "   Length  : " . strlen($hashed) . " chars\n";

// Verify password
$valid = Hash::check($password, $hashed);
echo "   Valid   : " . ($valid ? 'Yes' : 'No') . "\n";

$wrong = Hash::check('WrongPassword', $hashed);
echo "   Wrong   : " . ($wrong ? 'Yes' : 'No') . "\n";

// ── 2. Custom Options ───────────────────────────────────────────
echo "\n2. Custom Hash Options\n";
$customHash = Hash::make($password, ['cost' => 12]);
echo "   Cost 12: {$customHash}\n";

// ── 3. Rehash Detection ─────────────────────────────────────────
echo "\n3. Rehash Detection\n";
$needsRehash = Hash::needsRehash($hashed, ['cost' => 12]);
echo "   Needs rehash (cost 12): " . ($needsRehash ? 'Yes' : 'No') . "\n";

$oldHash = '$2y$10$' . str_repeat('a', 53); // Simulasi hash lama
$needsRehashOld = Hash::needsRehash($oldHash);
echo "   Old hash needs rehash: " . ($needsRehashOld ? 'Yes' : 'No') . "\n";

// ── 4. Random Token Generation ──────────────────────────────────
echo "\n4. Random Token Generation\n";
$token20 = Hash::randomToken(20);
echo "   20 chars: {$token20}\n";
echo "   Length  : " . strlen($token20) . "\n";

$token40 = Hash::randomToken(40);
echo "   40 chars: {$token40}\n";

$token64 = Hash::randomToken(64);
echo "   64 chars: {$token64}\n";

// ── 5. Timing-Safe Compare ──────────────────────────────────────
echo "\n5. Timing-Safe Compare\n";
$known = 'secret-token-12345';
$userInput1 = 'secret-token-12345';
$userInput2 = 'secret-token-99999';

$safe1 = Hash::equals($known, $userInput1);
$safe2 = Hash::equals($known, $userInput2);
echo "   Same string  : " . ($safe1 ? 'Match' : 'No match') . "\n";
echo "   Diff string  : " . ($safe2 ? 'Match' : 'No match') . "\n";
echo "   (Prevents timing attacks)\n";

// ── 6. Use Cases ────────────────────────────────────────────────
echo "\n6. Common Use Cases\n";
echo "   Password storage  : Hash::make(\$password)\n";
echo "   Login verify      : Hash::check(\$input, \$stored)\n";
echo "   Remember tokens   : Hash::randomToken(40)\n";
echo "   API keys          : Hash::randomToken(32)\n";
echo "   CSRF tokens       : Hash::randomToken(32)\n";
echo "   Recovery tokens   : Hash::randomToken(64)\n";

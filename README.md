# DarkAuth

[![Latest Stable Version](https://img.shields.io/packagist/v/darkauth/darkauth.svg)](https://packagist.org/packages/darkauth/darkauth)
[![License](https://img.shields.io/packagist/l/darkauth/darkauth.svg)](https://packagist.org/packages/darkauth/darkauth)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4.33-8892bf.svg)](https://packagist.org/packages/darkauth/darkauth)

**DarkAuth** adalah library autentikasi modern, fleksibel, dan berstandar produksi untuk **CodeIgniter 3** dan PHP 7.4+. Mendukung Session, JWT, MFA, Risk-Based Authentication, Trusted Devices, Rate Limiting, dan Audit Trail.

---

## Table of Contents

- [Fitur Utama](#-fitur-utama)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Getting Started](#-getting-started)
- [Use Cases](#-use-cases)
  - [1. Session Login (Web)](#1-session-login-web)
  - [2. JWT API Authentication](#2-jwt-api-authentication)
  - [3. Multi-Factor Authentication (TOTP)](#3-multi-factor-authentication-totp)
  - [4. Recovery Codes](#4-recovery-codes)
  - [5. Password Recovery Workflow](#5-password-recovery-workflow)
  - [6. Trusted Devices](#6-trusted-devices)
  - [7. Rate Limiting](#7-rate-limiting)
  - [8. CAPTCHA Integration](#8-captcha-integration)
  - [9. Audit Trail & Monitoring](#9-audit-trail--monitoring)
  - [10. Adaptive Risk Engine](#10-adaptive-risk-engine)
  - [11. Custom User Provider](#11-custom-user-provider)
  - [12. Custom Guard Driver](#12-custom-guard-driver)
- [API Reference](#-api-reference)
- [Database Schema](#-database-schema)
- [Security Best Practices](#-security-best-practices)
- [Struktur Proyek](#-struktur-proyek)
- [Lisensi](#-lisensi)

---

## Fitur Utama

- **Multi-Driver Architecture**: Session (Web) dan JWT (API) dengan API seragam.
- **Advanced MFA**: TOTP (Google Authenticator) + Recovery Codes native.
- **Adaptive Security**: Risk Engine mendeteksi anomali IP/Device, memicu tantangan otomatis.
- **Trusted Devices**: Token HMAC untuk bypass MFA di perangkat tepercaya.
- **Rate Limiting**: Database-backed rate limiter dengan atomic lock support.
- **Audit Trail**: Log keamanan tamper-resistant dengan tanda tangan HMAC.
- **CAPTCHA**: Google reCAPTCHA v2 integration.
- **Government-Friendly UX**: Template HTML/CSS ramah pengguna senior.
- **SOLID & Modular**: PSR-4, mudah ditest, ORM-agnostic via callback-based storage.

---

## Instalasi

```bash
composer require darkauth/darkauth
```

Jalankan skema database (opsional, untuk fitur lanjut):

```sql
-- Lihat database.sql untuk skema lengkap
-- Minimal: buat tabel users sendiri
```

### Requirements

- PHP >= 7.4.33
- `firebase/php-jwt` ^6.10 (otomatis terinstall via Composer)

---

## Konfigurasi

### 1. Salin config

```bash
cp config/auth.php application/config/auth.php
```

### 2. Konfigurasi Provider

```php
'providers' => [
    'users' => [
        'driver'    => 'database',       // Driver: 'database' atau custom
        'table'     => 'users',          // Nama tabel (untuk driver database)
        'callback'  => function($id) {   // Callback lookup user by ID
            $CI =& get_instance();
            $row = $CI->db->get_where('users', ['id' => $id])->row();
            return $row ? new GenericUser((array) $row) : null;
        }
    ],
],
```

### 3. Konfigurasi Guard

```php
'guards' => [
    'web' => [
        'driver'   => 'session',       // Session-based (cookie)
        'provider' => 'users',
    ],
    'api' => [
        'driver'   => 'jwt',           // Token-based (Bearer)
        'provider' => 'users',
    ],
],
```

### 4. Konfigurasi JWT

```php
'jwt' => [
    'secret' => env('JWT_SECRET', 'your-256-bit-secret-key-here'),
    'algo'   => 'HS256',
    'ttl'    => 3600,      // 1 jam
    'issuer' => 'myapp',   // Optional: issuer claim
],
```

### 5. Konfigurasi MFA

```php
'mfa' => [
    'enabled' => true,
    'issuer'  => 'Nama Aplikasi Anda',
],
```

### 6. Konfigurasi CAPTCHA

```php
'captcha' => [
    'driver' => 'recaptcha',
    'drivers' => [
        'recaptcha' => [
            'site_key'   => env('RECAPTCHA_SITE_KEY'),
            'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        ],
    ],
],
```

### 7. Konfigurasi Audit

```php
'audit' => [
    'enabled'  => true,
    'hmac_key' => env('AUDIT_HMAC_KEY', 'your-secure-random-key'),
    'callback' => function($logData) {
        $CI =& get_instance();
        $CI->db->insert('darkauth_audit_logs', $logData);
    },
],
```

### 8. Konfigurasi Trusted Device

```php
'trusted_device' => [
    'hmac_key' => env('TRUSTED_DEVICE_KEY', 'your-device-token-key'),
    'callback' => function($action, $key, $value = null) {
        $CI =& get_instance();
        switch ($action) {
            case 'get':
                return $CI->db->get_where('darkauth_trusted_devices', ['token_key' => $key])->row();
            case 'set':
                $CI->db->insert('darkauth_trusted_devices', ['token_key' => $key, 'data' => $value]);
                break;
            case 'remove':
                $CI->db->delete('darkauth_trusted_devices', ['token_key' => $key]);
                break;
        }
    },
],
```

### 9. Konfigurasi Rate Limiter

```php
'rate_limit' => [
    'callback' => function($action, $key, $value = null) {
        $CI =& get_instance();
        // Implementasi sesuai kebutuhan
    },
    'lock_callback' => function($key, callable $fn) {
        // Atomic lock untuk mencegah race condition
        return $fn();
    },
],
```

---

## Getting Started

### CodeIgniter 3 Integration

```php
// 1. Enable composer autoload di application/config/config.php
$config['composer_autoload'] = TRUE;

// 2. Load di controller
class Dashboard extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library(\Darkauth\Support\CI3Auth::class, null, 'auth');
    }

    public function index() {
        if ($this->auth->check()) {
            $user = $this->auth->user();
            echo "Welcome, " . $user->name;
        } else {
            redirect('login');
        }
    }
}
```

### Standalone PHP (Tanpa Framework)

```php
<?php
require_once 'vendor/autoload.php';

use Darkauth\Auth\AuthManager;
use Darkauth\Models\GenericUser;

$config = require 'config/auth.php';
$auth = new AuthManager($config);

// Login
$user = new GenericUser(['id' => 1, 'name' => 'Admin']);
$auth->guard('web')->login($user);

// Check
if ($auth->check()) {
    echo $auth->user()->name;
}
```

---

## Use Cases

### 1. Session Login (Web)

**Scenario**: Form login tradisional dengan cookie-based session.

```php
<?php
// Contoh: controllers/Auth.php (CI3)

public function login() {
    $this->load->library(\Darkauth\Support\CI3Auth::class, null, 'auth');

    if ($this->input->method() === 'post') {
        $credentials = [
            'username' => $this->input->post('username'),
            'password' => $this->input->post('password'),
        ];

        if ($this->auth->attempt($credentials)) {
            // Redirect ke dashboard setelah berhasil
            redirect('dashboard');
        }

        $data['error'] = 'Username atau password salah.';
    }

    $this->load->view('login', $data ?? []);
}

public function logout() {
    $this->auth->logout();
    redirect('login');
}
```

**Metode tersedia**:
- `attempt($credentials, $remember = false)` - Login dengan validasi
- `login($user, $remember = false)` - Login langsung dengan objek user
- `loginUsingId($id, $remember = false)` - Login by user ID
- `check()` - Cek apakah user terotentikasi
- `guest()` - Cek apakah user guest
- `user()` - Ambil objek user
- `id()` - Ambil user ID
- `logout()` - Logout

**Lihat**: `examples/basic_login.php`

---

### 2. JWT API Authentication

**Scenario**: REST API stateless dengan Bearer token.

```php
<?php
// Contoh: controllers/Api/Auth.php (CI3)

public function login() {
    $username = $this->input->post('username');
    $password = $this->input->post('password');

    $guard = $this->auth->guard('api');

    // Validate credentials via provider
    $provider = $this->auth->getProvider('users');
    $user = $provider->retrieveByCredentials([
        'username' => $username,
        'password' => $password,
    ]);

    if ($user && $provider->validateCredentials($user, ['password' => $password])) {
        $token = $guard->issueToken($user, [
            'role' => $user->role ?? 'user',
        ]);

        echo json_encode([
            'status'  => 'success',
            'token'   => $token,
            'expires' => 3600,
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
    }
}

// Middleware: Protected endpoint
public function profile() {
    $guard = $this->auth->guard('api');

    if (!$guard->check()) {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
        return;
    }

    $user = $guard->user();
    echo json_encode([
        'id'    => $user->id,
        'name'  => $user->name,
        'email' => $user->email,
    ]);
}
```

**Metode JWTGuard**:
- `issueToken($user, $extraPayload = [])` - Generate JWT token
- `setToken($token)` - Set token manual (dari header)
- `check()` - Validasi token dari Authorization header
- `user()` - Ambil user dari token

**Lihat**: `examples/api_jwt.php`

---

### 3. Multi-Factor Authentication (TOTP)

**Scenario**: Setup Google Authenticator / Authy.

```php
<?php
// STEP 1: Generate secret & QR code
$mfa = $this->auth->mfa();
$secret = $mfa->generateSecret();
$qrUrl = $mfa->getQrCodeUrl($user->email, $secret, 'NamaAplikasi');

// Simpan $secret ke database user
$this->db->update('users', ['mfa_secret' => $secret], ['id' => $user->id]);

// STEP 2: Tampilkan QR code ke user
echo Templates::mfaOnboarding($qrUrl, $secret);

// STEP 3: Verifikasi kode dari user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['mfa_code'];
    if ($mfa->verify($secret, $code)) {
        // MFA aktif, tandai di database
        $this->db->update('users', ['mfa_enabled' => 1], ['id' => $user->id]);
    }
}
```

**Metode MFA**:
- `generateSecret()` - Generate secret key 32-char base32
- `verify($secret, $code)` - Verifikasi kode 6 digit TOTP
- `getQrCodeUrl($username, $secret, $issuer)` - URL untuk QR code (otpauth://)

**Lihat**: `examples/mfa_complete_flow.php`

---

### 4. Recovery Codes

**Scenario**: Backup codes saat user tidak punya akses ke authenticator.

```php
<?php
use Darkauth\MFA\RecoveryCodes;

$recovery = new RecoveryCodes();

// Generate 8 recovery codes
$codes = $recovery->generate(8);
// Output: ['a1b2c3d4', 'e5f6g7h8', ...]

// Simpan hashed codes ke database
foreach ($codes as $code) {
    $hashed = Hash::make($code);
    $this->db->insert('darkauth_recovery_codes', [
        'user_id' => $user->id,
        'code'    => $hashed,
    ]);
}

// Tampilkan ke user sekali saja
echo Templates::recoveryCodes($codes);
```

**Lihat**: `examples/mfa_recovery_codes.php`

---

### 5. Password Recovery Workflow

**Scenario**: User lupa password, minta reset via email.

```php
<?php
// STEP 1: User minta reset
public function forgot_password() {
    $email = $this->input->post('email');
    $user = $provider->retrieveByCredentials(['email' => $email]);

    if ($user) {
        $recovery = $this->auth->getRecoveryWorkflow();
        $token = $recovery->createToken($user->id, 900); // 15 menit

        // Kirim email
        $link = base_url("reset-password?token={$token}&id={$user->id}");
        send_reset_email($user->email, $link);
    }

    // Selalu tampilkan pesan sukses (mencegah user enumeration)
    echo "Jika email terdaftar, link reset telah dikirim.";
}

// STEP 2: User klik link
public function reset_password() {
    $token = $this->input->get('token');
    $userId = $this->input->get('id');

    $recovery = $this->auth->getRecoveryWorkflow();

    if ($recovery->verifyToken($userId, $token)) {
        // Tampilkan form reset password
        $this->load->view('reset_password', compact('token', 'userId'));
    } else {
        show_error('Token tidak valid atau sudah kedaluwarsa.', 400);
    }
}

// STEP 3: User submit password baru
public function do_reset() {
    $token = $this->input->post('token');
    $userId = $this->input->post('user_id');
    $newPassword = $this->input->post('password');

    $recovery = $this->auth->getRecoveryWorkflow();

    if ($recovery->verifyToken($userId, $token)) {
        // Update password
        $hashed = Hash::make($newPassword);
        $this->db->update('users', ['password' => $hashed], ['id' => $userId]);

        // Invalidate token
        $recovery->complete($userId, $token);

        echo "Password berhasil diubah.";
    }
}
```

**Metode RecoveryWorkflow**:
- `createToken($userId, $expirySeconds = 3600)` - Generate reset token
- `verifyToken($userId, $token)` - Cek validitas token
- `complete($userId, $token = null)` - Hapus token setelah berhasil. `$token` wajib (aman); jika `null` jatuh ke jalur lama yang deprecated + `error_log`.

**Lihat**: `examples/advanced_recovery.php`

---

### 6. Trusted Devices

**Scenario**: "Remember this device" untuk bypass MFA.

```php
<?php
// Saat user centang "Ingat perangkat ini"
$device = $this->auth->getTrustedDeviceManager();
$token = $device->issue($user->id, 30); // 30 hari

// Simpan token di cookie
setcookie('device_token', $token, [
    'expires'  => time() + (86400 * 30),
    'path'     => '/',
    'secure'   => true,
    'httponly'  => true,
    'samesite' => 'strict',
]);

// Saat user login lagi, cek device
$device = $this->auth->getTrustedDeviceManager();
if (isset($_COOKIE['device_token'])) {
    if ($device->verify($user->id, $_COOKIE['device_token'])) {
        // Device trusted, bypass MFA
        skip_mfa_verification();
    }
}

// Revoke semua device (saat user ganti password)
$device->revokeAll($user->id);
```

**Metode TrustedDevice**:
- `issue($userId, $days)` - Generate device token (HMAC-signed)
- `verify($userId, $token)` - Cek token valid & belum expired
- `revokeAll($userId)` - Revoke semua trusted devices user
- `isRevoked($userId, $tokenHash)` - Cek apakah token di-revoke

**Lihat**: `examples/trusted_device.php`

---

### 7. Rate Limiting

**Scenario**: Limit login attempts untuk brute-force protection.

```php
<?php
// Di login controller
$limiter = $this->auth->getRateLimiter();
$key = 'login:' . $this->input->ip_address();
$maxAttempts = 5;
$decaySeconds = 900; // 15 menit

if ($limiter->tooManyAttempts($key, $maxAttempts)) {
    $seconds = $limiter->availableIn($key);
    show_error("Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.", 429);
}

if (!$this->auth->attempt($credentials)) {
    $limiter->hit($key, $decaySeconds);
    $remaining = $limiter->remaining($key, $maxAttempts);

    echo json_encode([
        'message'   => 'Login gagal.',
        'remaining' => $remaining,
    ]);
} else {
    $limiter->clear($key); // Reset on success
}

// Reset setelah password berhasil diubah
$limiter->clear('login:' . $userId);
```

**Metode DatabaseRateLimiter**:
- `tooManyAttempts($key, $maxAttempts)` - Cek apakah limit tercapai
- `hit($key, $decaySeconds)` - Increment counter
- `remaining($key, $maxAttempts)` - Sisa percobaan
- `availableIn($key)` - Detik tersisa sampai reset
- `clear($key)` - Reset counter

**Lihat**: `examples/rate_limiting.php`

---

### 8. CAPTCHA Integration

**Scenario**: Proteksi form dengan Google reCAPTCHA v2.

```php
<?php
// Di login controller
$captcha = $this->auth->captcha();
$siteKey = $captcha->getSiteKey();

// Render di view
echo "<form method='POST'>";
echo "  <div class='g-recaptcha' data-sitekey='{$siteKey}'></div>";
echo "  <button type='submit'>Login</button>";
echo "</form>";

// Verifikasi di controller
$captchaResponse = $_POST['g-recaptcha-response'] ?? '';
$ip = $this->input->ip_address();

if (!$captcha->verify($captchaResponse, $ip)) {
    show_error('CAPTCHA verification failed.', 400);
}
```

**Metode CaptchaInterface**:
- `verify($response, $ip = null)` - Verifikasi response token
- `getSiteKey()` - Ambil site key untuk render

**Lihat**: `examples/captcha_integration.php`

---

### 9. Audit Trail & Monitoring

**Scenario**: Log semua aktivitas keamanan untuk compliance.

```php
<?php
use Darkauth\Events\Dispatcher;
use Darkauth\Audit\AuditLogger;

// Setup
$events = new Dispatcher();

$audit = new AuditLogger(function($logData) {
    // Simpan ke database
    $CI =& get_instance();
    $CI->db->insert('darkauth_audit_logs', [
        'event'      => $logData['event'],
        'user_id'    => $logData['user_id'] ?? null,
        'ip_address' => $logData['ip_address'],
        'data'       => json_encode($logData['data']),
        'signature'  => $logData['signature'],  // HMAC tamper-proof
        'created_at' => $logData['timestamp'],
    ]);
}, 'your-hmac-secret-key');

$audit->subscribe($events);

// Semua event auth otomatis ter-log
$events->dispatch('auth.login.success', ['user_id' => 1, 'ip' => '1.2.3.4']);
$events->dispatch('auth.login.failed', ['username' => 'admin', 'reason' => 'wrong_password']);
$events->dispatch('auth.mfa.success', ['user_id' => 1]);
$events->dispatch('auth.mfa.failed', ['user_id' => 1]);
$events->dispatch('auth.password.changed', ['user_id' => 1]);
$events->dispatch('auth.risk.detected', ['user_id' => 1, 'score' => 85]);
```

**Event yang tersedia**:
- `auth.login.success` - Login berhasil
- `auth.login.failed` - Login gagal
- `auth.logout` - User logout
- `auth.mfa.success` - MFA verifikasi berhasil
- `auth.mfa.failed` - MFA verifikasi gagal
- `auth.password.changed` - Password diubah
- `auth.risk.detected` - Risk score tinggi terdeteksi
- `auth.device.trusted` - Device ditambahkan ke trusted
- `auth.recovery.token_created` - Recovery token dibuat

**Lihat**: `examples/audit_and_monitoring.php`

---

### 10. Adaptive Risk Engine

**Scenario**: Deteksi login mencurigakan berdasarkan konteks.

```php
<?php
use Darkauth\Security\RiskEngine;
use Darkauth\Security\SecurityProfile;

$riskEngine = new RiskEngine();
$profiles = new SecurityProfile();

// Load profile
$profile = $profiles->get('standard');
// $profile = $profiles->get('hardened');  // Lebih ketat
// $profile = $profiles->get('government'); // Paling ketat

// Build context dari login attempt
$context = [
    'ip'              => $this->input->ip_address(),
    'last_ip'         => $user->last_ip ?? null,
    'ua'              => $this->input->user_agent(),
    'recent_failures' => get_recent_failures($user->id),
];

// Hitung risk score
$score = $riskEngine->calculateScore($user, $context);

// Decision logic
if ($riskEngine->shouldChallenge($score, $profile['captcha_threshold'] * 10)) {
    // Tampilkan CAPTCHA
    require_captcha();
}

if ($riskEngine->shouldChallenge($score, $profile['mfa_enforced'] ? 30 : 70)) {
    // Paksa MFA
    require_mfa();
}

if ($score > 80) {
    // Blokir login, kirim notifikasi
    notify_admin($user, $score);
}
```

**SecurityProfile levels**:
| Level | Session Timeout | MFA | Captcha Threshold | Rate Limit |
|-------|----------------|-----|-------------------|------------|
| `basic` | 2 jam | No | 10 | 100/menit |
| `standard` | 1 jam | No | 5 | 60/menit |
| `hardened` | 30 menit | Yes | 3 | 30/menit |
| `government` | 15 menit | Yes | 1 | 20/menit |

**Lihat**: `examples/adaptive_risk_check.php`

---

### 11. Custom User Provider

**Scenario**: Gunakan tabel/ORM sendiri (Eloquent, Doctrine, dll).

```php
<?php
use Darkauth\Core\UserProviderInterface;
use Darkauth\Core\UserInterface;
use Darkauth\Models\GenericUser;

class EloquentUserProvider implements UserProviderInterface {
    protected $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function retrieveById($identifier): ?UserInterface {
        $record = $this->model::find($identifier);
        return $record ? new GenericUser($record->toArray()) : null;
    }

    public function retrieveByCredentials(array $credentials): ?UserInterface {
        $query = $this->model::query();
        foreach ($credentials as $key => $value) {
            if ($key !== 'password') {
                $query->where($key, $value);
            }
        }
        $record = $query->first();
        return $record ? new GenericUser($record->toArray()) : null;
    }

    public function validateCredentials(UserInterface $user, array $credentials): bool {
        return password_verify($credentials['password'], $user->getAuthPassword());
    }
}

// Register ke config
$config['providers']['users']['callback'] = function($id) {
    $provider = new EloquentUserProvider(new User());
    return $provider->retrieveById($id);
};
```

**Lihat**: `examples/custom_user_provider.php`

---

### 12. Custom Guard Driver

**Scenario**: Tambah driver auth sendiri (misal: LDAP, OAuth).

```php
<?php
use Darkauth\Auth\AuthManager;
use Darkauth\Core\GuardInterface;

class LDAPGuard implements GuardInterface {
    protected $ldap;
    protected $user;

    public function __construct($ldapConfig) {
        $this->ldap = $ldapConfig;
    }

    public function check(): bool {
        return $this->user !== null;
    }

    public function guest(): bool {
        return !$this->check();
    }

    public function user(): ?UserInterface {
        return $this->user;
    }

    public function id() {
        return $this->user ? $this->user->getAuthIdentifier() : null;
    }

    public function validate(array $credentials = []): bool {
        // LDAP bind logic
        return @ldap_bind($this->ldap, $credentials['dn'], $credentials['password']);
    }

    public function setUser(UserInterface $user): void {
        $this->user = $user;
    }
}

// Register driver
$auth = new AuthManager($config);
$auth->extend('ldap', function ($app, $name, $config) {
    return new LDAPGuard($config['ldap']);
});

// Use it
$guard = $auth->guard('ldap');
```

**Lihat**: `examples/custom_guard_driver.php`

---

## API Reference

### AuthManager

| Metode | Return | Deskripsi |
|--------|--------|-----------|
| `guard($name)` | `GuardInterface` | Ambil guard instance |
| `getDefaultGuard()` | `string` | Nama default guard |
| `mfa()` | `MFAInterface` | MFA TOTP driver |
| `captcha($name)` | `CaptchaInterface` | CAPTCHA driver |
| `getRiskEngine()` | `RiskEngine` | Risk assessment engine |
| `getSecurityProfile()` | `SecurityProfile` | Security profiles |
| `getRecoveryWorkflow()` | `RecoveryWorkflow` | Password recovery |
| `getTrustedDeviceManager()` | `TrustedDevice` | Trusted device manager |
| `getRateLimiter()` | `DatabaseRateLimiter` | Rate limiter |
| `events()` | `Dispatcher` | Event dispatcher |
| `extend($driver, $callback)` | `$this` | Register custom driver |
| `revokeAllSessionsForUser($userId)` | `void` | Revoke all sessions |

### GuardInterface

| Metode | Return | Deskripsi |
|--------|--------|-----------|
| `check()` | `bool` | User terotentikasi? |
| `guest()` | `bool` | User guest? |
| `user()` | `?UserInterface` | User object |
| `id()` | `mixed` | User ID |
| `validate($credentials)` | `bool` | Validasi kredensial |
| `setUser($user)` | `void` | Set user manual |

### StatefulGuardInterface (SessionGuard)

| Metode | Return | Deskripsi |
|--------|--------|-----------|
| `attempt($credentials, $remember)` | `bool` | Login dengan validasi |
| `login($user, $remember)` | `void` | Login langsung |
| `loginUsingId($id, $remember)` | `void` | Login by ID |
| `logout()` | `void` | Logout |

### JWTGuard

| Metode | Return | Deskripsi |
|--------|--------|-----------|
| `issueToken($user, $extra)` | `string` | Generate JWT |
| `setToken($token)` | `$this` | Set token manual |

### Hash Utility

| Metode | Return | Deskripsi |
|--------|--------|-----------|
| `Hash::make($value)` | `string` | Hash value (bcrypt) |
| `Hash::check($value, $hashed)` | `bool` | Verify hash |
| `Hash::needsRehash($hashed)` | `bool` | Perlu rehash? |
| `Hash::randomToken($length)` | `string` | Random token |
| `Hash::equals($known, $user)` | `bool` | Timing-safe compare |

### GenericUser

| Metode | Return | Deskripsi |
|--------|--------|-----------|
| `__construct($attributes)` | - | User dari array |
| `$user->key` | `mixed` | Akses properti via __get |
| `getAttributes()` | `array` | Semua atribut |
| `getAuthIdentifier()` | `mixed` | ID user |
| `getAuthPassword()` | `string` | Hashed password |
| `setRememberToken($value)` | `void` | Set remember token |

---

## Database Schema

```sql
-- Tabel users (minimal)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(128) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    mfa_secret VARCHAR(64) DEFAULT NULL,
    mfa_enabled TINYINT(1) DEFAULT 0,
    last_ip VARCHAR(45),
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk fitur lanjut (lihat database.sql)
-- darkauth_mfa_secrets
-- darkauth_trusted_devices
-- darkauth_audit_logs
```

---

## Security Best Practices

1. **JWT Secret**: Gunakan minimal 256-bit key. Jangan gunakan di config yang di-commit ke git.
2. **HMAC Keys**: Gunakan `bin2hex(random_bytes(32))` untuk audit & trusted device HMAC keys.
3. **Rate Limiting**: Selalu implement rate limiting di login endpoint.
4. **MFA**: Aktifkan untuk user dengan role admin/privileged.
5. **Recovery Codes**: Selalu generate recovery codes saat user setup MFA.
6. **Trusted Device**: Set expiry yang wajar (7-30 hari).
7. **Audit**: Log semua aktivitas auth untuk debugging & compliance.
8. **CAPTCHA**: Gunakan di login & registration form.
9. **Password**: Enforce minimal 8 karakter, mixed case + numbers.
10. **Session**: Gunakan HTTPS-only cookies, `SameSite=Strict`.

---

## Struktur Proyek

```
src/
├── Audit/
│   └── AuditLogger.php              # Tamper-resistant audit log
├── Auth/
│   ├── AuthManager.php              # Central orchestrator
│   ├── DatabaseUserProvider.php     # DB-backed user lookup
│   └── RecoveryWorkflow.php         # Password recovery flow
├── Captcha/
│   ├── CaptchaInterface.php
│   └── ReCaptchaDriver.php          # Google reCAPTCHA v2
├── Core/
│   ├── AuthenticatableTrait.php
│   ├── AuthenticationException.php
│   ├── GuardInterface.php
│   ├── StatefulGuardInterface.php
│   ├── StorageInterface.php
│   ├── UserInterface.php
│   └── UserProviderInterface.php
├── Drivers/
│   ├── JWTGuard.php                 # Stateless JWT guard
│   └── SessionGuard.php             # Stateful session guard
├── Events/
│   └── Dispatcher.php               # Event system
├── MFA/
│   ├── MFAInterface.php
│   ├── RecoveryCodes.php            # Backup codes generator
│   └── TOTPDriver.php               # Google Authenticator
├── Middleware/
│   └── Authenticate.php             # Auth middleware
├── Models/
│   └── GenericUser.php              # Generic user model
├── Security/
│   ├── DatabaseRateLimiter.php      # DB-backed rate limiter
│   ├── RateLimiterInterface.php
│   ├── RiskEngine.php               # Adaptive risk scoring
│   ├── SecurityProfile.php          # Security presets
│   └── TrustedDevice.php            # HMAC-signed device tokens
└── Support/
    ├── CI3Auth.php                  # CodeIgniter 3 wrapper
    ├── Hash.php                     # Password hashing utility
    ├── JwtHelper.php                # JWT generate/decode
    ├── SessionStorage.php           # CI3 session adapter
    ├── Mail/
    │   └── MailNotifierInterface.php
    └── UI/
        └── Templates.php            # Government-friendly HTML templates
```

---

## Lisensi

Proyek ini dilisensikan di bawah **MIT License**.

---

**Dibuat dengan ❤️ untuk ekosistem PHP Indonesia.**

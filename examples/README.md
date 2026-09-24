# DarkAuth Examples

Contoh penggunaan lengkap untuk semua fitur DarkAuth.

## Daftar Examples

| # | File | Deskripsi | Fitur |
|---|------|-----------|-------|
| 1 | `basic_login.php` | Session-based login (web) | SessionGuard, login/logout/check |
| 2 | `api_jwt.php` | JWT API authentication | JWTGuard, issueToken, setToken |
| 3 | `mfa_complete_flow.php` | Setup Google Authenticator | TOTPDriver, QR code, verify |
| 4 | `mfa_recovery_codes.php` | Backup codes untuk MFA | RecoveryCodes, hash, sekali pakai |
| 5 | `advanced_recovery.php` | Reset password via email | RecoveryWorkflow, token expiry |
| 6 | `trusted_device_bypass.php` | "Remember this device" | TrustedDevice, HMAC tokens |
| 7 | `rate_limiting.php` | Brute-force protection | DatabaseRateLimiter, atomic lock |
| 8 | `audit_and_monitoring.php` | Security audit trail | AuditLogger, HMAC tamper-proof |
| 9 | `adaptive_risk_check.php` | Risk-based authentication | RiskEngine, SecurityProfile |
| 10 | `captcha_integration.php` | Google reCAPTCHA v2 | ReCaptchaDriver, verify |
| 11 | `ci3_integration.php` | CodeIgniter 3 integration | CI3Auth, middleware |
| 12 | `custom_user_provider.php` | Custom ORM/database provider | UserProviderInterface |
| 13 | `custom_guard_driver.php` | Custom auth driver (API key) | AuthManager::extend() |
| 14 | `hash_utility.php` | Password hashing & tokens | Hash utility |
| 15 | `full_stack_example.php` | Semua fitur dalam satu flow | Kombinasi lengkap |
| 16 | `database_setup.php` | Database schema & PDO setup | Schema SQL, migrations |

## Menjalankan Examples

```bash
# Install dependencies
composer install

# Jalankan individual example
php examples/basic_login.php
php examples/api_jwt.php
php examples/mfa_complete_flow.php

# Atau semua sekaligus
for f in examples/*.php; do echo "=== $f ==="; php "$f"; echo; done
```

## Catatan

- Semua examples berjalan tanpa framework (standalone PHP)
- `basic_login.php` dan `api_jwt.php` paling dasar — mulai dari sini
- `full_stack_example.php` menunjukkan integrasi lengkap semua fitur
- `database_setup.php` membantu setup tabel untuk MySQL/PostgreSQL/SQLite

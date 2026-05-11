# DarkAuth

[![Latest Stable Version](https://img.shields.io/packagist/v/darkauth/darkauth.svg)](https://packagist.org/packages/darkauth/darkauth)
[![License](https://img.shields.io/packagist/l/darkauth/darkauth.svg)](https://packagist.org/packages/darkauth/darkauth)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4.33-8892bf.svg)](https://packagist.org/packages/darkauth/darkauth)

**DarkAuth** adalah library autentikasi modern, fleksibel, dan berstandar produksi yang dirancang khusus untuk membawa pola keamanan mutakhir ke aplikasi **CodeIgniter 3** dan lingkungan PHP 7.4+.

---

## ✨ Fitur Utama

- 🛡️ **Multi-Driver Architecture**: Switch antara `Session` (Web) dan `JWT` (API) dengan satu API yang seragam.
- 🔐 **Advanced MFA**: Mendukung **TOTP (Google Authenticator)** dan **Recovery Codes** secara native.
- 🤖 **Adaptive Security**: Dilengkapi dengan **Risk Engine** untuk mendeteksi anomali IP/Perangkat dan memicu tantangan keamanan secara otomatis.
- 🧱 **SOLID & Modular**: Arsitektur yang bersih, mudah ditest, dan mengikuti standar PSR-4.
- 📋 **Audit Trail**: Pencatatan log keamanan yang tahan manipulasi (*Tamper-resistant*) dengan tanda tangan digital (HMAC).
- 👴 **Government-Friendly UX**: Template UI yang dirancang khusus untuk pengguna non-teknis/senior dengan instruksi yang jelas.
- ⚡ **Plug-and-Play**: Integrasi mudah ke CodeIgniter 3 tanpa mengubah file core framework.

---

## 🚀 Instalasi

Instalasi melalui Composer:

```bash
composer require darkauth/darkauth
```

Lalu jalankan skema database yang diperlukan (opsional namun direkomendasikan untuk fitur lanjut):
```bash
# Lihat file database.sql untuk skema tabel
```

---

## ⚙️ Konfigurasi Dasar (CI3)

1. Salin file `config/auth.php` ke folder `application/config/` Anda.
2. Load library di controller Anda:
```php
$this->load->library(\Darkauth\Support\CI3Auth::class, null, 'darkauth');
```

---

## 📖 Panduan Penggunaan

### 1. Autentikasi Dasar (Web)
```php
// Login user
if ($this->darkauth->attempt(['username' => 'admin', 'password' => 'rahasia'])) {
    redirect('dashboard');
}

// Cek status
if ($this->darkauth->check()) {
    $user = $this->darkauth->user();
    echo "Halo, " . $user->username;
}

// Logout
$this->darkauth->logout();
```

### 2. Autentikasi API (JWT)
```php
// Mengambil guard API
$guard = $this->darkauth->guard('api');

// Issue Token
$token = $guard->issueToken($user);

// Verifikasi (Otomatis membaca header Authorization: Bearer ...)
if ($guard->check()) {
    return $guard->user();
}
```

### 3. Implementasi MFA (TOTP)
```php
$mfa = $this->darkauth->mfa();

// Buat Secret Baru (Saat pendaftaran MFA)
$secret = $mfa->generateSecret();
$qrUrl = $mfa->getQrCodeUrl('user@email.com', $secret, 'NamaSistem');

// Verifikasi Kode dari HP
if ($mfa->verify($secret, $this->input->post('otp_code'))) {
    echo "MFA Berhasil!";
}
```

### 4. Adaptive Security (Risk-Based Auth)
DarkAuth dapat mendeteksi risiko secara otomatis untuk memicu tantangan tambahan:
```php
$riskScore = $this->darkauth->getRiskEngine()->calculateScore($user, [
    'ip' => $this->input->ip_address(),
    'ua' => $this->input->user_agent()
]);

if ($riskScore > 70) {
    // Paksa Captcha atau MFA karena ada kecurigaan
    $this->session->set_userdata('require_mfa', true);
}
```

### 5. Audit Logging
Setiap kejadian penting dicatat otomatis jika Anda mengaktifkan listener:
```php
$this->darkauth->events()->listen('auth.login.failed', function($payload) {
    // Logika kustom saat login gagal (misal: kirim email peringatan)
});
```

---

## 📁 Struktur Proyek
- `src/Core`: Interface dan kontrak dasar.
- `src/Drivers`: Implementasi Session dan JWT.
- `src/MFA`: Logika TOTP dan Recovery Codes.
- `src/Security`: Risk Engine, Rate Limiter, dan Trusted Devices.
- `src/Audit`: Sistem Audit Log tamper-resistant.
- `src/Support/UI`: Template HTML/CSS ramah pengguna senior.

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah **MIT License**. Silakan gunakan secara bebas untuk proyek komersial maupun personal.

---

**Dibuat dengan ❤️ untuk ekosistem PHP Indonesia.**

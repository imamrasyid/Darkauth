<?php

namespace Darkauth\Support\UI;

/**
 * Class Templates
 * 
 * Provides accessible, government-style HTML snippets for senior users.
 */
class Templates
{
    /**
     * Get CSS styles for government UX.
     */
    public static function styles(): string
    {
        return '
        <style>
            .darkauth-container { font-family: sans-serif; max-width: 400px; margin: 50px auto; padding: 30px; border: 1px solid #ccc; border-radius: 8px; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .darkauth-title { font-size: 24px; font-weight: bold; margin-bottom: 20px; color: #333; text-align: center; }
            .darkauth-text { font-size: 16px; line-height: 1.5; color: #666; margin-bottom: 20px; }
            .darkauth-input { width: 100%; padding: 12px; margin: 10px 0; border: 2px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 18px; }
            .darkauth-button { width: 100%; padding: 14px; background: #0056b3; color: #fff; border: none; border-radius: 4px; font-size: 18px; font-weight: bold; cursor: pointer; }
            .darkauth-button:hover { background: #004494; }
            .darkauth-qr { display: block; margin: 20px auto; max-width: 200px; }
            .darkauth-alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        </style>';
    }

    /**
     * Get MFA Onboarding Template.
     */
    public static function mfaOnboarding(string $qrCodeUrl, string $secret): string
    {
        return '
        <div class="darkauth-container">
            <h1 class="darkauth-title">Keamanan Tambahan</h1>
            <p class="darkauth-text">Untuk menjaga keamanan akun Anda, silakan pindai kode QR di bawah menggunakan aplikasi <strong>Google Authenticator</strong> di HP Anda.</p>
            <img src="' . $qrCodeUrl . '" class="darkauth-qr" alt="QR Code">
            <p class="darkauth-text" style="text-align:center;">Atau masukkan kode ini secara manual:<br><strong>' . $secret . '</strong></p>
            <form method="POST">
                <input type="text" name="mfa_code" class="darkauth-input" placeholder="Masukkan 6 angka dari HP" required maxlength="6" pattern="\d{6}">
                <button type="submit" class="darkauth-button">Aktifkan Sekarang</button>
            </form>
        </div>';
    }

    /**
     * Get Recovery Codes Template.
     */
    public static function recoveryCodes(array $codes): string
    {
        $codesList = implode('</div><div style="flex:1 0 40%; padding:5px; font-family:monospace; font-size:18px;">', $codes);
        return '
        <div class="darkauth-container">
            <h1 class="darkauth-title">Simpan Kode Pemulihan</h1>
            <p class="darkauth-text">Jika HP Anda hilang, Anda dapat menggunakan kode-kode di bawah ini untuk masuk kembali ke akun Anda. <strong>Mohon simpan di tempat yang aman (bisa dicetak).</strong></p>
            <div style="display:flex; flex-wrap:wrap; background:#f9f9f9; padding:15px; border-radius:4px; border:1px dashed #ccc;">
                <div style="flex:1 0 40%; padding:5px; font-family:monospace; font-size:18px;">' . $codesList . '</div>
            </div>
            <button onclick="window.print()" class="darkauth-button" style="margin-top:20px; background:#6c757d;">Cetak Kode</button>
            <a href="dashboard" class="darkauth-button" style="display:block; text-align:center; text-decoration:none; margin-top:10px;">Selesai, Masuk ke Dashboard</a>
        </div>';
    }
}

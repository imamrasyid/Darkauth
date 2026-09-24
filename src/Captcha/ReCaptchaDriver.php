<?php

namespace Darkauth\Captcha;

/**
 * Class ReCaptchaDriver
 * 
 * Google reCAPTCHA v2/v3 driver.
 */
class ReCaptchaDriver implements CaptchaInterface
{
    protected $siteKey;
    protected $secretKey;

    public function __construct(string $siteKey, string $secretKey)
    {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
    }

    public function verify(string $response, string $ip = null): bool
    {
        if (empty($response)) return false;

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $this->secretKey,
            'response' => $response,
        ];
        if ($ip !== null) {
            $data['remoteip'] = $ip;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false || $httpCode !== 200) {
            return false;
        }

        $json = json_decode($result, true);
        return $json['success'] ?? false;
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }
}

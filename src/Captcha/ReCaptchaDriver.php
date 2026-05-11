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
            'remoteip' => $ip
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $json = json_decode($result, true);

        return $json['success'] ?? false;
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }
}

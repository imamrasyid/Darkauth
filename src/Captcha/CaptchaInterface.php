<?php

namespace Darkauth\Captcha;

/**
 * Interface CaptchaInterface
 */
interface CaptchaInterface
{
    /**
     * Verify the captcha response.
     *
     * @param string $response
     * @param string|null $ip
     * @return bool
     */
    public function verify(string $response, string $ip = null): bool;

    /**
     * Get the client-side configuration/key.
     *
     * @return string
     */
    public function getSiteKey(): string;
}

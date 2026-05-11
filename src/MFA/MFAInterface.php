<?php

namespace Darkauth\MFA;

/**
 * Interface MFAInterface
 */
interface MFAInterface
{
    /**
     * Generate a secret for the user.
     *
     * @return string
     */
    public function generateSecret(): string;

    /**
     * Verify the given code against the secret.
     *
     * @param string $secret
     * @param string $code
     * @return bool
     */
    public function verify(string $secret, string $code): bool;

    /**
     * Get the QR code URL for the secret.
     *
     * @param string $username
     * @param string $secret
     * @param string $issuer
     * @return string
     */
    public function getQrCodeUrl(string $username, string $secret, string $issuer): string;
}

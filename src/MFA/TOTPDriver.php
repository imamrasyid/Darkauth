<?php

namespace Darkauth\MFA;

/**
 * Class TOTPDriver
 * 
 * Google Authenticator (TOTP) implementation according to RFC 6238.
 */
class TOTPDriver implements MFAInterface
{
    /**
     * @inheritDoc
     */
    public function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * @inheritDoc
     */
    public function verify(string $secret, string $code): bool
    {
        $timeSlice = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            $calculated = $this->getCode($secret, $timeSlice + $i);
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getQrCodeUrl(string $username, string $secret, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($username) . 
               '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
    }

    /**
     * Calculate the code for the given secret and time slice.
     */
    protected function getCode(string $secret, int $time): string
    {
        $secret = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $time);
        $hash = hash_hmac('sha1', $time, $secret, true);
        $offset = ord($hash[19]) & 0xf;
        $otp = (
            (ord($hash[$offset + 0]) & 0x7f) << 24 |
            (ord($hash[$offset + 1]) & 0xff) << 16 |
            (ord($hash[$offset + 2]) & 0xff) << 8 |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, 6);

        return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
    }

    protected function base32Decode(string $base32): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $map = array_flip(str_split($chars));
        $base32 = strtoupper($base32);
        $output = '';
        $buffer = 0;
        $bufferSize = 0;

        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            if ($char === '=') break;
            if (!isset($map[$char])) continue;

            $buffer = ($buffer << 5) | $map[$char];
            $bufferSize += 5;

            if ($bufferSize >= 8) {
                $output .= chr(($buffer >> ($bufferSize - 8)) & 0xff);
                $bufferSize -= 8;
            }
        }

        return $output;
    }
}

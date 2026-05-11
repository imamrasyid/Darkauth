<?php

namespace Darkauth\MFA;

use Darkauth\Support\Hash;

/**
 * Class RecoveryCodes
 * 
 * Handles generation and verification of recovery codes.
 */
class RecoveryCodes
{
    /**
     * Generate a set of recovery codes.
     *
     * @param int $amount
     * @return array
     */
    public function generate(int $amount = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $amount; $i++) {
            $codes[] = Hash::randomToken(10);
        }
        return $codes;
    }
}

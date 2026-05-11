<?php

namespace Darkauth\Security;

/**
 * Class SecurityProfile
 * 
 * Manages predefined security configurations.
 */
class SecurityProfile
{
    const BASIC = 'basic';
    const STANDARD = 'standard';
    const HARDENED = 'hardened';
    const GOVERNMENT = 'government';

    protected $profiles = [
        self::BASIC => [
            'session_timeout' => 7200, // 2 hours
            'mfa_enforced' => false,
            'captcha_threshold' => 10,
            'rate_limit' => 100,
            'password_policy' => ['min_length' => 6]
        ],
        self::STANDARD => [
            'session_timeout' => 3600, // 1 hour
            'mfa_enforced' => false,
            'captcha_threshold' => 5,
            'rate_limit' => 60,
            'password_policy' => ['min_length' => 8, 'mixed_case' => true]
        ],
        self::HARDENED => [
            'session_timeout' => 1800, // 30 mins
            'mfa_enforced' => true,
            'captcha_threshold' => 3,
            'rate_limit' => 30,
            'password_policy' => ['min_length' => 12, 'mixed_case' => true, 'symbols' => true]
        ],
        self::GOVERNMENT => [
            'session_timeout' => 900, // 15 mins
            'mfa_enforced' => true,
            'captcha_threshold' => 1,
            'rate_limit' => 20,
            'password_policy' => ['min_length' => 14, 'mixed_case' => true, 'symbols' => true, 'numbers' => true]
        ]
    ];

    public function get(string $mode): array
    {
        return $this->profiles[$mode] ?? $this->profiles[self::STANDARD];
    }
}

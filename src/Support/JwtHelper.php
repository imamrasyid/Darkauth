<?php

namespace Darkauth\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * Class JwtHelper
 * 
 * Simple wrapper for firebase/php-jwt.
 */
class JwtHelper
{
    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $algo;

    /**
     * JwtHelper constructor.
     *
     * @param string $secret
     * @param string $algo
     */
    public function __construct(string $secret, string $algo = 'HS256')
    {
        $this->secret = $secret;
        $this->algo = $algo;
    }

    /**
     * Generate a JWT token.
     *
     * @param array $payload
     * @param int $expiry Seconds from now
     * @return string
     */
    public function generate(array $payload, int $expiry = 3600): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Decode and validate a JWT token.
     *
     * @param string $token
     * @return array|null
     */
    public function decode(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
}

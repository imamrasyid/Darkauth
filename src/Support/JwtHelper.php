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
     * @var string|null
     */
    protected $issuer;

    /**
     * JwtHelper constructor.
     *
     * @param string $secret
     * @param string $algo
     * @param string|null $issuer Expected issuer claim for validation
     */
    public function __construct(string $secret, string $algo = 'HS256', string $issuer = null)
    {
        $this->secret = $secret;
        $this->algo = $algo;
        $this->issuer = $issuer;
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

        if ($this->issuer !== null) {
            $payload['iss'] = $this->issuer;
        }

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
            $claims = (array) $decoded;

            if ($this->issuer !== null && isset($claims['iss']) && $claims['iss'] !== $this->issuer) {
                return null;
            }

            return $claims;
        } catch (Exception $e) {
            return null;
        }
    }
}

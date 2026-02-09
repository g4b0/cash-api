<?php

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function generateAccessToken(int $memberId, int $communityId): string
    {
        $payload = [
            'sub' => $memberId,
            'cid' => $communityId,
            'type' => 'access',
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function generateRefreshToken(int $memberId, int $communityId): string
    {
        $payload = [
            'sub' => $memberId,
            'cid' => $communityId,
            'type' => 'refresh',
            'iat' => time(),
            'exp' => time() + 604800,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * @return object Decoded token payload
     * @throws \Exception on invalid or expired token
     */
    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, 'HS256'));
    }
}

<?php

namespace App\Http\Response;

/**
 * Response for authentication endpoints that return JWT tokens.
 *
 * Returns 200 OK status with access and refresh tokens.
 *
 * Example:
 *   Status: 200 OK
 *   Body: {"access_token": "eyJ...", "refresh_token": "eyJ..."}
 */
class TokenPairResponse extends AppResponse
{
    public string $accessToken;
    public string $refreshToken;

    /**
     * @param string $accessToken The JWT access token
     * @param string $refreshToken The JWT refresh token
     */
    public function __construct(string $accessToken, string $refreshToken)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }
}

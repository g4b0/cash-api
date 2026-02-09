<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Service\JwtService;
use flight\Engine;
use PDO;

class AuthController
{
    private Engine $app;

    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    public function login(): void
    {
        $username = $this->app->request()->data->username;
        $password = $this->app->request()->data->password;

        if (empty($username)) {
            throw AppException::USERNAME_REQUIRED();
        }

        if (empty($password)) {
            throw AppException::PASSWORD_REQUIRED();
        }

        $db = $this->app->get('db');
        $stmt = $db->prepare('SELECT id, community_id, password FROM member WHERE username = ?');
        $stmt->execute([$username]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member || !password_verify($password, $member['password'])) {
            throw AppException::INVALID_CREDENTIALS();
        }

        $jwtService = new JwtService($this->app->get('jwt_secret'));

        $this->app->json([
            'access_token' => $jwtService->generateAccessToken((int) $member['id'], (int) $member['community_id']),
            'refresh_token' => $jwtService->generateRefreshToken((int) $member['id'], (int) $member['community_id']),
        ]);
    }

    public function refresh(): void
    {
        $refreshToken = $this->app->request()->data->refresh_token;

        if (empty($refreshToken)) {
            throw AppException::REFRESH_TOKEN_REQUIRED();
        }

        $jwtService = new JwtService($this->app->get('jwt_secret'));

        try {
            $decoded = $jwtService->decode($refreshToken);

            if (($decoded->type ?? '') !== 'refresh') {
                throw AppException::INVALID_TOKEN_TYPE();
            }

            $this->app->json([
                'access_token' => $jwtService->generateAccessToken((int) $decoded->sub, (int) $decoded->cid),
                'refresh_token' => $jwtService->generateRefreshToken((int) $decoded->sub, (int) $decoded->cid),
            ]);
        } catch (AppException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw AppException::INVALID_OR_EXPIRED_TOKEN();
        }
    }
}

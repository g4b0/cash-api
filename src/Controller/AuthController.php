<?php

namespace App\Controller;

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
            $this->app->json(['error' => 'Username is required'], 400);
            return;
        }

        if (empty($password)) {
            $this->app->json(['error' => 'Password is required'], 400);
            return;
        }

        $db = $this->app->get('db');
        $stmt = $db->prepare('SELECT id, community_id, password FROM member WHERE username = ?');
        $stmt->execute([$username]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member || !password_verify($password, $member['password'])) {
            $this->app->json(['error' => 'Invalid credentials'], 401);
            return;
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
            $this->app->json(['error' => 'Refresh token is required'], 400);
            return;
        }

        $jwtService = new JwtService($this->app->get('jwt_secret'));

        try {
            $decoded = $jwtService->decode($refreshToken);

            if (($decoded->type ?? '') !== 'refresh') {
                $this->app->json(['error' => 'Invalid token type'], 401);
                return;
            }

            $this->app->json([
                'access_token' => $jwtService->generateAccessToken((int) $decoded->sub, (int) $decoded->cid),
                'refresh_token' => $jwtService->generateRefreshToken((int) $decoded->sub, (int) $decoded->cid),
            ]);
        } catch (\Exception $e) {
            $this->app->json(['error' => 'Invalid or expired refresh token'], 401);
        }
    }
}

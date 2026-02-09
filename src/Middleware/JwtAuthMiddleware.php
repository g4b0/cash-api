<?php

namespace App\Middleware;

use App\Service\JwtService;
use flight\Engine;

class JwtAuthMiddleware
{
    private const PUBLIC_ROUTES = ['/login', '/refresh'];

    public static function register(Engine $app): void
    {
        $publicRoutes = self::PUBLIC_ROUTES;

        $app->map('start', function () use ($app, $publicRoutes) {
            $url = $app->request()->url;

            if (!in_array($url, $publicRoutes, true)) {
                $authHeader = $app->request()->getHeader('Authorization');
                if (empty($authHeader) || !preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
                    $app->json(['error' => 'Unauthorized'], 401);
                    return;
                }

                try {
                    $jwtService = new JwtService($app->get('jwt_secret'));
                    $decoded = $jwtService->decode($matches[1]);

                    if (($decoded->type ?? '') !== 'access') {
                        $app->json(['error' => 'Unauthorized'], 401);
                        $app->error();
                        return;
                    }

                    $app->set('auth_user', $decoded);
                } catch (\Exception $e) {
                    $app->json(['error' => 'Unauthorized'], 401);
                    return;
                }
            }

            $app->_start();
        });
    }
}

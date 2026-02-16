<?php

namespace App\Http\Middleware;

use App\Exception\AppException;
use App\Service\JwtService;
use flight\Engine;

class JwtAuthMiddleware
{
    private const PUBLIC_ROUTES = ['/login', '/refresh'];

    public static function register(Engine $app): void
    {
        $publicRoutes = self::PUBLIC_ROUTES;

        $app->map('start', function () use ($app, $publicRoutes) {
            try {
                $url = $app->request()->url;

                if (!in_array($url, $publicRoutes, true)) {
                    $authHeader = $app->request()->getHeader('Authorization');
                    if (empty($authHeader) || !preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
                        throw AppException::UNAUTHORIZED();
                    }

                    try {
                        $jwtService = new JwtService($app->get('jwt_secret'));
                        $decoded = $jwtService->decode($matches[1]);

                        if (($decoded->type ?? '') !== 'access') {
                            throw AppException::UNAUTHORIZED();
                        }

                        $app->set('auth_user', $decoded);
                    } catch (AppException $e) {
                        throw $e;
                    } catch (\Exception $e) {
                        throw AppException::UNAUTHORIZED();
                    }
                }

                $app->_start();
            } catch (\Throwable $e) {
                $app->handleException($e);
            }
        });
    }
}

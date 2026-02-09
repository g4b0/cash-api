<?php

use App\Controller\AuthController;
use App\Controller\DashboardController;
use App\Middleware\JwtAuthMiddleware;
use flight\Engine;

function registerRoutes(Engine $app): void
{
    JwtAuthMiddleware::register($app);

    $app->route('POST /login', [AuthController::class, 'login']);
    $app->route('POST /refresh', [AuthController::class, 'refresh']);

    $app->route('GET /@community_id/@member_id', [DashboardController::class, 'show']);
}

<?php

use App\Controller\AuthController;
use App\Controller\DashboardController;
use App\Middleware\ExceptionHandler;
use App\Middleware\JwtAuthMiddleware;
use flight\Engine;

function registerRoutes(Engine $app): void
{
    ExceptionHandler::register($app);
    JwtAuthMiddleware::register($app);

    $app->route('POST /login', [AuthController::class, 'login']);
    $app->route('POST /refresh', [AuthController::class, 'refresh']);

    $app->route('GET /@community_id/@member_id', [DashboardController::class, 'show']);
}

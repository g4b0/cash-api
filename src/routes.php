<?php

use App\Controller\AuthController;
use App\Controller\DashboardController;
use App\Controller\ExpenseController;
use App\Controller\IncomeController;
use App\Middleware\ExceptionHandler;
use App\Middleware\JwtAuthMiddleware;
use flight\Engine;

function registerRoutes(Engine $app): void
{
    ExceptionHandler::register($app);
    JwtAuthMiddleware::register($app);

    $app->route('POST /login', [AuthController::class, 'login']);
    $app->route('POST /refresh', [AuthController::class, 'refresh']);

    // Income endpoints (must come before dashboard route to avoid route collision)
    $app->route('POST /income', [IncomeController::class, 'create']);
    $app->route('GET /income/@id', [IncomeController::class, 'read']);
    $app->route('PATCH /income/@id', [IncomeController::class, 'update']);
    $app->route('DELETE /income/@id', [IncomeController::class, 'delete']);

    // Expense endpoints
    $app->route('POST /expense', [ExpenseController::class, 'create']);
    $app->route('GET /expense/@id', [ExpenseController::class, 'read']);
    $app->route('PATCH /expense/@id', [ExpenseController::class, 'update']);
    $app->route('DELETE /expense/@id', [ExpenseController::class, 'delete']);

    $app->route('GET /@community_id/@member_id', [DashboardController::class, 'show']);
}

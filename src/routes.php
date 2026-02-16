<?php

use App\Http\Controller\AuthController;
use App\Http\Controller\ExpenseController;
use App\Http\Controller\IncomeController;
use App\Http\Controller\TransactionsController;
use App\Http\Middleware\ExceptionHandler;
use App\Http\Middleware\JwtAuthMiddleware;
use flight\Engine;

function registerRoutes(Engine $app): void
{
    ExceptionHandler::register($app);
    JwtAuthMiddleware::register($app);

    $app->route('POST /login', [AuthController::class, 'login']);
    $app->route('POST /refresh', [AuthController::class, 'refresh']);

    // Income endpoints
    $app->route('POST /income', [IncomeController::class, 'create']);
    $app->route('GET /income/@id', [IncomeController::class, 'read']);
    $app->route('PUT /income/@id', [IncomeController::class, 'update']);
    $app->route('DELETE /income/@id', [IncomeController::class, 'delete']);

    // Expense endpoints
    $app->route('POST /expense', [ExpenseController::class, 'create']);
    $app->route('GET /expense/@id', [ExpenseController::class, 'read']);
    $app->route('PUT /expense/@id', [ExpenseController::class, 'update']);
    $app->route('DELETE /expense/@id', [ExpenseController::class, 'delete']);

    // Transactions
    $app->route('GET /balance/@communityId/@memberId', [TransactionsController::class, 'balance']);

    $app->route('GET /transactions/@communityId/@memberId(/@num(/@page))', [TransactionsController::class, 'list']);
}

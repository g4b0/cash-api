<?php

namespace App\Http\Middleware;

use App\Exception\AppException;
use flight\Engine;

class ExceptionHandler
{
    public static function register(Engine $app): void
    {
        $app->map('error', function (\Throwable $e) use ($app) {
            if ($e instanceof AppException) {
                $app->response()->header('Content-Type', 'application/json');
                $app->halt($e->getStatusCode(), json_encode(['error' => $e->getMessage()]), empty(getenv('PHPUNIT_TEST')));
            } else {
                // Delegate to default error handler for non-AppException errors
                $app->_error($e);
            }
        });
    }
}

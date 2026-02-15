<?php

namespace App\Controller;

use App\Response\AppResponse;
use flight\Engine;
use PDO;

/**
 * Abstract base controller providing common functionality.
 */
abstract class Controller
{
    protected Engine $app;

    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Get the authenticated user from JWT middleware.
     *
     * @return object JWT payload with sub (memberId) and cid (communityId)
     */
    protected function getAuthUser(): object
    {
        return $this->app->get('auth_user');
    }

    /**
     * Get the database connection.
     */
    protected function getDb(): PDO
    {
        return $this->app->get('db');
    }

    /**
     * Return a JSON response.
     *
     * Supports both AppResponse objects and raw data for backward compatibility.
     * When using AppResponse objects, status code and Location header are extracted
     * automatically from the response object.
     *
     * @param mixed $data AppResponse object or raw data (array, etc.)
     * @param int $statusCode HTTP status code (ignored if $data is AppResponse)
     */
    protected function json($data, int $statusCode = 200): void
    {
        if ($data instanceof AppResponse) {
            // Extract status code and Location header from response object
            $statusCode = $data->getStatusCode();

            if ($data->getLocationHeader() !== null) {
                $this->app->response()->header('Location', $data->getLocationHeader());
            }

            // JsonSerializable interface handles array conversion
            $this->app->json($data, $statusCode);
        } else {
            // Backward compatibility: support raw arrays during migration
            $this->app->json($data, $statusCode);
        }
    }
}

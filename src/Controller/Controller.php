<?php

namespace App\Controller;

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
     * @return object JWT payload with sub (member_id) and cid (community_id)
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
     * @param mixed $data
     * @param int $statusCode
     */
    protected function json($data, int $statusCode = 200): void
    {
        $this->app->json($data, $statusCode);
    }
}

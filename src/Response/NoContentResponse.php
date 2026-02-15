<?php

namespace App\Response;

/**
 * Response for DELETE endpoints that successfully delete a resource.
 *
 * Returns 204 No Content status with empty body.
 *
 * Example:
 *   Status: 204 No Content
 *   Body: (empty)
 */
class NoContentResponse extends AppResponse
{
    public function getStatusCode(): int
    {
        return 204;
    }
}

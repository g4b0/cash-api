<?php

namespace App\Response;

/**
 * Response for GET and PUT endpoints that return a full entity.
 *
 * Returns 200 OK status with the complete entity data.
 *
 * Example:
 *   Status: 200 OK
 *   Body: {"id": 1, "amount": 1000, "reason": "Salary", ...}
 */
class EntityResponse extends AppResponse
{
    private array $entity;

    /**
     * @param array $entity The entity data to return
     */
    public function __construct(array $entity)
    {
        $this->entity = $entity;
    }

    public function toArray(): array
    {
        return $this->entity;
    }
}

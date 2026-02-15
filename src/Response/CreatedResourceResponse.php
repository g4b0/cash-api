<?php

namespace App\Response;

/**
 * Response for POST endpoints that create a new resource.
 *
 * Returns 201 Created status with resource ID and Location header.
 * Complies with RFC 9110 (HTTP Semantics).
 *
 * Example:
 *   Status: 201 Created
 *   Location: /income/123
 *   Body: {"id": 123}
 */
class CreatedResourceResponse extends AppResponse
{
    public int $id;
    private string $resourceType; // private: used only for Location header, not serialized

    /**
     * @param int $id The ID of the created resource
     * @param string $resourceType The resource type for the Location header (e.g., "income", "expense")
     */
    public function __construct(int $id, string $resourceType)
    {
        $this->id = $id;
        $this->resourceType = $resourceType;
    }

    public function getStatusCode(): int
    {
        return 201;
    }

    public function getLocationHeader(): ?string
    {
        return "/{$this->resourceType}/{$this->id}";
    }
}

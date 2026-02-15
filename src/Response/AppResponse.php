<?php

namespace App\Response;

use JsonSerializable;

/**
 * Abstract base class for all API responses.
 *
 * Implements JsonSerializable for automatic serialization by Flight::json().
 * Subclasses define response structure via toArray() and optionally override
 * getStatusCode() and getLocationHeader() for HTTP-specific behavior.
 */
abstract class AppResponse implements JsonSerializable
{
    /**
     * Convert response to array for JSON serialization.
     *
     * @return array Response data structure
     */
    abstract public function toArray(): array;

    /**
     * Get HTTP status code for this response.
     *
     * @return int HTTP status code (default: 200 OK)
     */
    public function getStatusCode(): int
    {
        return 200;
    }

    /**
     * Get Location header value (for 201 Created responses).
     *
     * @return string|null Location header value, or null if not applicable
     */
    public function getLocationHeader(): ?string
    {
        return null;
    }

    /**
     * Implement JsonSerializable interface.
     *
     * @return array Data to be serialized to JSON
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

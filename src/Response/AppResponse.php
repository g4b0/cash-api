<?php

namespace App\Response;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use DateTime;

/**
 * Abstract base class for all API responses.
 *
 * Implements JsonSerializable for automatic serialization by Flight::json().
 * Subclasses define response structure via public properties.
 *
 * Composable design:
 * - Public properties are automatically serialized via reflection
 * - Supports nested Response objects (converted recursively)
 * - Supports arrays of Response objects
 * - DateTime objects automatically formatted as ISO 8601
 */
abstract class AppResponse implements JsonSerializable
{
    /**
     * Convert response to array for JSON serialization using reflection.
     *
     * Automatically converts all public properties:
     * - Scalars (int, float, string, bool, null) → returned as-is
     * - Arrays → recursively converted (may contain Response objects)
     * - AppResponse objects → converted via toArray()
     * - DateTime objects → formatted as ISO 8601 (Y-m-d H:i:s or Y-m-d)
     *
     * @return array Response data structure
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $result = [];

        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($this);

            $result[$name] = $this->convertValue($value);
        }

        return $result;
    }

    /**
     * Recursively convert a value to a JSON-serializable format.
     *
     * @param mixed $value The value to convert
     * @return mixed Converted value
     */
    private function convertValue($value)
    {
        // Null - return as-is
        if ($value === null) {
            return $value;
        }

        // Float - round to 2 decimal places for currency/monetary values
        if (is_float($value)) {
            return round($value, 2);
        }

        // Other scalar types - return as-is
        if (is_scalar($value)) {
            return $value;
        }

        // Arrays - recursively convert each element
        if (is_array($value)) {
            return array_map(fn($item) => $this->convertValue($item), $value);
        }

        // AppResponse objects - convert via toArray()
        if ($value instanceof AppResponse) {
            return $value->toArray();
        }

        // DateTime objects - format as ISO 8601
        if ($value instanceof DateTime) {
            return $this->formatDateTime($value);
        }

        // Generic objects (stdClass, etc.) - convert to associative array
        if (is_object($value)) {
            $array = (array) $value;
            return array_map(fn($item) => $this->convertValue($item), $array);
        }

        // Fallback: return null for unsupported types
        return null;
    }

    /**
     * Format DateTime object for JSON serialization.
     *
     * Uses different formats based on time component:
     * - If time is 00:00:00 → date only (Y-m-d)
     * - Otherwise → full datetime (Y-m-d H:i:s)
     *
     * @param DateTime $dateTime
     * @return string Formatted date/datetime string
     */
    private function formatDateTime(DateTime $dateTime): string
    {
        // Check if time is midnight (date-only value)
        if ($dateTime->format('H:i:s') === '00:00:00') {
            return $dateTime->format('Y-m-d');
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

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

<?php

namespace App\Response;

/**
 * Response for dashboard endpoints that return a single named metric.
 *
 * Returns 200 OK status with a metric name and value.
 *
 * Example:
 *   Status: 200 OK
 *   Body: {"balance": 1250.50}
 */
class MetricResponse extends AppResponse
{
    private string $name;
    private $value;

    /**
     * @param string $name The metric name (e.g., "balance")
     * @param mixed $value The metric value (typically float, int, or string)
     */
    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function toArray(): array
    {
        return [$this->name => $this->value];
    }
}

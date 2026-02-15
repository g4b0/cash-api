<?php

namespace App\Response;

/**
 * Response for balance endpoint.
 *
 * Returns member's balance (contributions minus expenses).
 *
 * Example:
 *   Status: 200 OK
 *   Body: {"memberId": 1, "balance": "625.50"}
 */
class BalanceResponse extends AppResponse
{
    public int $memberId;
    public string $balance; // string for JSON precision (avoids floating-point rounding issues)

    /**
     * @param int $memberId The member ID
     * @param float $balance The calculated balance
     */
    public function __construct(int $memberId, float $balance)
    {
        $this->memberId = $memberId;
        $this->balance = (string) $balance; // Convert float to string to preserve precision
    }
}

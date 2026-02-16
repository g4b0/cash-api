<?php

namespace App\Http\Response;

/**
 * Response for balance endpoint.
 *
 * Returns member's balance (contributions minus expenses).
 * Balance is rounded to 2 decimal places for currency display.
 *
 * Example:
 *   Status: 200 OK
 *   Body: {"memberId": 1, "balance": 625.50}
 */
class BalanceResponse extends AppResponse
{
    public int $memberId;
    public float $balance;

    /**
     * @param int $memberId The member ID
     * @param float $balance The calculated balance
     */
    public function __construct(int $memberId, float $balance)
    {
        $this->memberId = $memberId;
        $this->balance = $balance;
    }
}

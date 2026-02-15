<?php

namespace App\Response;

/**
 * Composable response for transaction lists.
 *
 * Combines an array of transaction responses (Income/Expense) with pagination metadata.
 *
 * Example:
 *   $response = new TransactionListResponse();
 *   $response->transactions = [$incomeResponse, $expenseResponse];
 *   $response->pagination = new Pagination(1, 5, 120, 25);
 *
 * Results in:
 *   {
 *     "transactions": [
 *       {"id": 1, "type": "income", ...},
 *       {"id": 2, "type": "expense", ...}
 *     ],
 *     "pagination": {
 *       "current_page": 1,
 *       "total_pages": 5,
 *       "total_items": 120,
 *       "per_page": 25
 *     }
 *   }
 */
class TransactionListResponse extends AppResponse
{
    /** @var array<IncomeResponse|ExpenseResponse> Transaction response objects */
    public array $transactions;

    /** @var Pagination Pagination metadata */
    public Pagination $pagination;
}
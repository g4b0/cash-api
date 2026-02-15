<?php

namespace App\Response;

/**
 * Composable response for transaction lists.
 *
 * Combines typed transaction responses (Income/Expense) with pagination metadata.
 *
 * Example:
 *   $response = new TransactionListResponse(1, 5, 120, 25);
 *   $response->pushIncome(new IncomeResponse($incomeData));
 *   $response->pushExpense(new ExpenseResponse($expenseData));
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
    public array $transactions = [];

    /** @var Pagination Pagination metadata */
    public Pagination $pagination;

    /**
     * @param int $currentPage Current page number
     * @param int $totalPages Total number of pages
     * @param int $totalItems Total number of items across all pages
     * @param int $perPage Items per page
     */
    public function __construct(
        int $currentPage,
        int $totalPages,
        int $totalItems,
        int $perPage
    ) {
        $this->pagination = new Pagination($currentPage, $totalPages, $totalItems, $perPage);
    }

    /**
     * Add an income transaction to the list.
     *
     * @param IncomeResponse $income Income transaction response
     */
    public function pushIncome(IncomeResponse $income): void
    {
        $this->transactions[] = $income;
    }

    /**
     * Add an expense transaction to the list.
     *
     * @param ExpenseResponse $expense Expense transaction response
     */
    public function pushExpense(ExpenseResponse $expense): void
    {
        $this->transactions[] = $expense;
    }
}
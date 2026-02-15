<?php

namespace App\Response;

/**
 * Pagination metadata for composable responses.
 *
 * Use this when you need just pagination metadata (not wrapped with data).
 * For backward compatibility with existing endpoints, use PaginatedResponse.
 *
 * Example usage in TransactionListResponse:
 *   public array $transactions;
 *   public Pagination $pagination;
 *
 * Results in:
 *   {
 *     "transactions": [...],
 *     "pagination": {
 *       "current_page": 1,
 *       "total_pages": 5,
 *       "total_items": 120,
 *       "per_page": 25
 *     }
 *   }
 */
class Pagination extends AppResponse
{
    public int $current_page;
    public int $total_pages;
    public int $total_items;
    public int $per_page;

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
        $this->current_page = $currentPage;
        $this->total_pages = $totalPages;
        $this->total_items = $totalItems;
        $this->per_page = $perPage;
    }
}

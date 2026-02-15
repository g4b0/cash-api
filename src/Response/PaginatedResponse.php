<?php

namespace App\Response;

/**
 * Response for list endpoints that return paginated data.
 *
 * Returns 200 OK status with data array and pagination metadata.
 *
 * Example:
 *   Status: 200 OK
 *   Body: {
 *     "data": [...],
 *     "pagination": {
 *       "current_page": 1,
 *       "total_pages": 5,
 *       "total_items": 120,
 *       "per_page": 25
 *     }
 *   }
 */
class PaginatedResponse extends AppResponse
{
    private array $data;
    private int $currentPage;
    private int $totalPages;
    private int $totalItems;
    private int $perPage;

    /**
     * @param array $data The paginated data items
     * @param int $currentPage Current page number
     * @param int $totalPages Total number of pages
     * @param int $totalItems Total number of items across all pages
     * @param int $perPage Items per page
     */
    public function __construct(
        array $data,
        int $currentPage,
        int $totalPages,
        int $totalItems,
        int $perPage
    ) {
        $this->data = $data;
        $this->currentPage = $currentPage;
        $this->totalPages = $totalPages;
        $this->totalItems = $totalItems;
        $this->perPage = $perPage;
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'pagination' => [
                'current_page' => $this->currentPage,
                'total_pages' => $this->totalPages,
                'total_items' => $this->totalItems,
                'per_page' => $this->perPage,
            ],
        ];
    }
}

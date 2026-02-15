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
    public array $data;
    public object $pagination; // Nested object with pagination metadata

    /**
     * @param array $data Data items
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
        $this->pagination = (object) [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'per_page' => $perPage,
        ];
    }
}

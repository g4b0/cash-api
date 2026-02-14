<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Validation\Validator;
use flight\Engine;
use PDO;

class TransactionsController
{
    private Engine $app;
    private Validator $validator;

    public function __construct(Engine $app)
    {
        $this->app = $app;
        $this->validator = new Validator();
    }

    public function list(
        string $community_id,
        string $member_id,
        ?string $num = null,
        ?string $page = null
    ): void {
        // Get authenticated user
        $authUser = $this->app->get('auth_user');
        $authCommunityId = (int) $authUser->cid;

        // Verify requested member exists and belongs to a community
        $db = $this->app->get('db');
        $stmt = $db->prepare('SELECT community_id FROM member WHERE id = ?');
        $stmt->execute([$member_id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            throw AppException::MEMBER_NOT_FOUND();
        }

        // Verify auth user is from the same community as requested member
        if ((int) $member['community_id'] !== $authCommunityId) {
            throw AppException::FORBIDDEN();
        }

        // Validate pagination parameters
        $perPage = $this->validator->validatePerPage($num);
        $currentPage = $this->validator->validatePage($page);

        // Count total items
        $stmt = $db->prepare('
            SELECT COUNT(*) FROM (
                SELECT id FROM income WHERE owner_id = ?
                UNION ALL
                SELECT id FROM expense WHERE owner_id = ?
            )
        ');
        $stmt->execute([$member_id, $member_id]);
        $totalItems = (int) $stmt->fetchColumn();

        // Calculate total pages
        $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $perPage) : 0;

        // Calculate offset
        $offset = ($currentPage - 1) * $perPage;

        // Fetch paginated data
        $stmt = $db->prepare('
            SELECT * FROM (
                SELECT
                    id,
                    "income" as type,
                    date,
                    reason,
                    amount,
                    contribution_percentage,
                    created_at,
                    updated_at
                FROM income WHERE owner_id = ?

                UNION ALL

                SELECT
                    id,
                    "expense" as type,
                    date,
                    reason,
                    amount,
                    NULL as contribution_percentage,
                    created_at,
                    updated_at
                FROM expense WHERE owner_id = ?
            )
            ORDER BY date DESC
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$member_id, $member_id, $perPage, $offset]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return response with data and pagination metadata
        $this->app->json([
            'data' => $transactions,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'per_page' => $perPage,
            ],
        ]);
    }
}

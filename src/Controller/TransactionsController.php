<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Repository\MemberRepository;
use App\Repository\TransactionRepository;
use App\Validation\Validator;
use flight\Engine;

class TransactionsController
{
    private Engine $app;
    private Validator $validator;
    private MemberRepository $memberRepository;
    private TransactionRepository $transactionRepository;

    public function __construct(Engine $app)
    {
        $this->app = $app;
        $this->validator = new Validator();
        $db = $app->get('db');
        $this->memberRepository = new MemberRepository($db);
        $this->transactionRepository = new TransactionRepository($db);
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
        $memberCommunityId = $this->memberRepository->getCommunityId((int) $member_id);

        if ($memberCommunityId === null) {
            throw AppException::MEMBER_NOT_FOUND();
        }

        // Verify auth user is from the same community as requested member
        if ($memberCommunityId !== $authCommunityId) {
            throw AppException::FORBIDDEN();
        }

        // Validate pagination parameters
        $perPage = $this->validator->validatePerPage($num);
        $currentPage = $this->validator->validatePage($page);

        // Count total items
        $totalItems = $this->transactionRepository->countByMemberId((int) $member_id);

        // Calculate total pages
        $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $perPage) : 0;

        // Calculate offset
        $offset = ($currentPage - 1) * $perPage;

        // Fetch paginated data
        $transactions = $this->transactionRepository->findPaginatedByMemberId(
            (int) $member_id,
            $perPage,
            $offset
        );

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

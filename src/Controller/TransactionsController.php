<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Repository\MemberRepository;
use App\Repository\TransactionRepository;
use App\Response\TransactionListResponse;
use App\Response\IncomeResponse;
use App\Response\ExpenseResponse;
use App\Response\BalanceResponse;
use App\Service\BalanceCalculator;
use App\Validation\Validator;
use flight\Engine;

class TransactionsController extends Controller
{
    private Validator $validator;
    private MemberRepository $memberRepository;
    private TransactionRepository $transactionRepository;

    public function __construct(Engine $app)
    {
        parent::__construct($app);
        $this->validator = new Validator();
        $this->memberRepository = new MemberRepository($this->getDb());
        $this->transactionRepository = new TransactionRepository($this->getDb());
    }

    public function list(
        string $communityId,
        string $memberId,
        ?string $num = null,
        ?string $page = null
    ): void {
        // Get authenticated user (JWT is signed and trusted - cid is guaranteed valid)
        $authUser = $this->getAuthUser();
        $authCommunityId = (int) $authUser->cid;

        // Verify target member exists and get their community (needed for authorization)
        $memberCommunityId = $this->memberRepository->getCommunityId((int) $memberId);

        if ($memberCommunityId === null) {
            throw AppException::MEMBER_NOT_FOUND();
        }

        // Verify target member is in same community as authenticated user
        if ($memberCommunityId !== $authCommunityId) {
            throw AppException::FORBIDDEN();
        }

        // Validate pagination parameters
        $perPage = $this->validator->validatePerPage($num);
        $currentPage = $this->validator->validatePage($page);

        // Count total items
        $totalItems = $this->transactionRepository->countByMemberId((int) $memberId);

        // Calculate total pages
        $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $perPage) : 0;

        // Calculate offset
        $offset = ($currentPage - 1) * $perPage;

        // Fetch paginated data
        $transactions = $this->transactionRepository->findPaginatedByMemberId(
            (int) $memberId,
            $perPage,
            $offset
        );

        // Create response and add typed transaction responses
        $response = new TransactionListResponse(
            $currentPage,
            $totalPages,
            $totalItems,
            $perPage
        );

        foreach ($transactions as $transaction) {
            if ($transaction['type'] === 'income') {
                $response->pushIncome(new IncomeResponse($transaction));
            } else {
                $response->pushExpense(new ExpenseResponse($transaction));
            }
        }

        $this->json($response);
    }

    public function balance(string $communityId, string $memberId): void
    {
        // Get authenticated user (JWT is signed and trusted - cid is guaranteed valid)
        $authUser = $this->getAuthUser();
        $authCommunityId = (int) $authUser->cid;

        // Verify target member exists and get their community (needed for authorization)
        $memberCommunityId = $this->memberRepository->getCommunityId((int) $memberId);

        if ($memberCommunityId === null) {
            throw AppException::MEMBER_NOT_FOUND();
        }

        // Verify target member is in same community as authenticated user
        if ($memberCommunityId !== $authCommunityId) {
            throw AppException::FORBIDDEN();
        }

        // Calculate and return balance
        $calculator = new BalanceCalculator($this->getDb());
        $balance = $calculator->calculate((int) $memberId);

        $this->json(new BalanceResponse((int) $memberId, $balance));
    }
}

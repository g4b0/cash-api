<?php

namespace App\Controller;

use App\Dto\ExpenseDto;
use App\Exception\AppException;
use App\Repository\ExpenseRepository;
use App\Repository\MemberRepository;
use App\Response\CreatedResourceResponse;
use App\Response\ExpenseResponse;
use App\Response\NoContentResponse;
use flight\Engine;

class ExpenseController extends Controller
{
    private MemberRepository $memberRepository;
    private ExpenseRepository $expenseRepository;

    public function __construct(Engine $app)
    {
        parent::__construct($app);
        $this->memberRepository = new MemberRepository($this->getDb());
        $this->expenseRepository = new ExpenseRepository($this->getDb());
    }

    public function create(): void
    {
        // 1. Get authenticated user
        $authUser = $this->getAuthUser();
        $memberId = (int) $authUser->sub;
        $communityId = (int) $authUser->cid;

        // 2. Verify member exists and belongs to community
        $member = $this->memberRepository->findByIdInCommunity($memberId, $communityId);

        if (!$member) {
            throw AppException::FORBIDDEN();
        }

        // 3. Validate input via DTO
        $dto = ExpenseDto::createFromRequest($this->app->request());

        // 4. Create expense record
        $expenseId = $this->expenseRepository->create($memberId, $dto);

        // 5. Return 201 Created with resource identifier (Location header set automatically)
        $this->json(new CreatedResourceResponse($expenseId, 'expense'));
    }

    public function read(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->getAuthUser();
        $communityId = (int) $authUser->cid;

        // 2. Fetch expense record
        $expense = $this->expenseRepository->findById((int) $id);

        if (!$expense) {
            throw AppException::EXPENSE_NOT_FOUND();
        }

        // 3. Verify community access (fetch owner's community)
        $ownerCommunityId = $this->memberRepository->getCommunityId((int) $expense['memberId']);

        if ($ownerCommunityId !== $communityId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Return record
        $this->json(new ExpenseResponse($expense));
    }

    public function update(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->getAuthUser();
        $memberId = (int) $authUser->sub;

        // 2. Fetch expense record
        $expense = $this->expenseRepository->findById((int) $id);

        if (!$expense) {
            throw AppException::EXPENSE_NOT_FOUND();
        }

        // 3. Verify ownership
        if ((int) $expense['memberId'] !== $memberId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Validate input via DTO
        $dto = ExpenseDto::createFromRequest($this->app->request());

        // 5. Execute update
        $this->expenseRepository->update((int) $id, $dto);

        // 6. Fetch updated record
        $updated = $this->expenseRepository->findById((int) $id);

        // 7. Return updated record
        $this->json(new ExpenseResponse($updated));
    }

    public function delete(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->getAuthUser();
        $memberId = (int) $authUser->sub;

        // 2. Fetch expense record
        $expense = $this->expenseRepository->findById((int) $id);

        if (!$expense) {
            throw AppException::EXPENSE_NOT_FOUND();
        }

        // 3. Verify ownership
        if ((int) $expense['memberId'] !== $memberId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Delete record
        $this->expenseRepository->delete((int) $id);

        // 5. Return 204 No Content
        $this->json(new NoContentResponse());
    }
}

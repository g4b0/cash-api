<?php

namespace App\Http\Controller;

use App\Database\Repository\ExpenseRepository;
use App\Database\Repository\MemberRepository;
use App\Exception\AppException;
use App\Http\Dto\ExpenseDto;
use App\Http\Response\Crud\CreatedResourceResponse;
use App\Http\Response\Crud\NoContentResponse;
use App\Http\Response\ExpenseResponse;
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
        // 1. Get authenticated user (JWT is signed and trusted)
        $authUser = $this->getAuthUser();
        $memberId = (int) $authUser->sub;

        // 2. Validate input via DTO
        $dto = ExpenseDto::createFromRequest($this->app->request());

        // 3. Create expense record
        $expenseId = $this->expenseRepository->create($memberId, $dto);

        // 4. Return 201 Created with resource identifier (Location header set automatically)
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

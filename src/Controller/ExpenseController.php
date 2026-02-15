<?php

namespace App\Controller;

use App\Dto\ExpenseDto;
use App\Exception\AppException;
use App\Repository\ExpenseRepository;
use App\Repository\MemberRepository;
use App\Validation\Validator;
use flight\Engine;

class ExpenseController extends Controller
{
    private Validator $validator;
    private MemberRepository $memberRepository;
    private ExpenseRepository $expenseRepository;

    public function __construct(Engine $app)
    {
        parent::__construct($app);
        $this->validator = new Validator();
        $this->memberRepository = new MemberRepository($this->getDb());
        $this->expenseRepository = new ExpenseRepository($this->getDb());
    }

    public function create(): void
    {
        // 1. Get authenticated user
        $authUser = $this->getAuthUser();
        $ownerId = (int) $authUser->sub;
        $communityId = (int) $authUser->cid;

        // 2. Verify member exists and belongs to community
        $member = $this->memberRepository->findByIdInCommunity($ownerId, $communityId);

        if (!$member) {
            throw AppException::FORBIDDEN();
        }

        // 3. Validate input via DTO
        $dto = ExpenseDto::createFromRequest($this->app->request());

        // 4. Create expense record
        $expenseId = $this->expenseRepository->create($ownerId, $dto);

        // 5. Fetch created record
        $expense = $this->expenseRepository->findById($expenseId);

        // 6. Return 201 Created
        $this->json($expense, 201);
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
        $ownerCommunityId = $this->memberRepository->getCommunityId((int) $expense['owner_id']);

        if ($ownerCommunityId !== $communityId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Return record
        $this->json($expense);
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
        if ((int) $expense['owner_id'] !== $memberId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Validate input via DTO
        $dto = ExpenseDto::createFromRequest($this->app->request());

        // 5. Execute update
        $this->expenseRepository->update((int) $id, $dto);

        // 6. Fetch updated record
        $updated = $this->expenseRepository->findById((int) $id);

        // 7. Return updated record
        $this->json($updated);
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
        if ((int) $expense['owner_id'] !== $memberId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Delete record
        $this->expenseRepository->delete((int) $id);

        // 5. Return 204 No Content
        $this->json(null, 204);
    }
}

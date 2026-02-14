<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Repository\ExpenseRepository;
use App\Repository\MemberRepository;
use App\Validation\Validator;
use flight\Engine;

class ExpenseController
{
    private Engine $app;
    private Validator $validator;
    private MemberRepository $memberRepository;
    private ExpenseRepository $expenseRepository;

    public function __construct(Engine $app)
    {
        $this->app = $app;
        $this->validator = new Validator();
        $db = $app->get('db');
        $this->memberRepository = new MemberRepository($db);
        $this->expenseRepository = new ExpenseRepository($db);
    }

    public function create(): void
    {
        // 1. Get authenticated user
        $authUser = $this->app->get('auth_user');
        $ownerId = (int) $authUser->sub;
        $communityId = (int) $authUser->cid;

        // 2. Verify member exists and belongs to community
        $member = $this->memberRepository->findByIdInCommunity($ownerId, $communityId);

        if (!$member) {
            throw AppException::FORBIDDEN();
        }

        // 3. Get request data
        $data = $this->app->request()->data;

        // 4. Validate required fields
        $amount = $this->validator->validateAmount($data->amount ?? null);
        $reason = $this->validator->validateReason($data->reason ?? null);
        $date = $this->validator->validateDate($data->date ?? null);

        // 5. Create expense record
        $expenseId = $this->expenseRepository->create($ownerId, $date, $reason, $amount);

        // 6. Fetch created record
        $expense = $this->expenseRepository->findById($expenseId);

        // 7. Return 201 Created
        $this->app->json($expense, 201);
    }

    public function read(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->app->get('auth_user');
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
        $this->app->json($expense);
    }

    public function update(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->app->get('auth_user');
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

        // 4. Get request data and validate provided fields
        $data = $this->app->request()->data;
        $updates = [];

        if (isset($data->amount)) {
            $updates['amount'] = $this->validator->validateAmount($data->amount);
        }
        if (isset($data->reason)) {
            $updates['reason'] = $this->validator->validateReason($data->reason);
        }
        if (isset($data->date)) {
            $updates['date'] = $this->validator->validateDate($data->date);
        }

        // 5. Execute update
        $this->expenseRepository->update((int) $id, $updates);

        // 6. Fetch updated record
        $updated = $this->expenseRepository->findById((int) $id);

        // 7. Return updated record
        $this->app->json($updated);
    }

    public function delete(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->app->get('auth_user');
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
        $this->app->json(null, 204);
    }
}

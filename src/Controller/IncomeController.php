<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Repository\IncomeRepository;
use App\Repository\MemberRepository;
use App\Validation\Validator;
use flight\Engine;

class IncomeController extends Controller
{
    private Validator $validator;
    private MemberRepository $memberRepository;
    private IncomeRepository $incomeRepository;

    public function __construct(Engine $app)
    {
        parent::__construct($app);
        $this->validator = new Validator();
        $this->memberRepository = new MemberRepository($this->getDb());
        $this->incomeRepository = new IncomeRepository($this->getDb());
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

        // 3. Get request data
        $data = $this->app->request()->data;

        // 4. Validate required fields
        $amount = $this->validator->validateAmount($data->amount ?? null);
        $reason = $this->validator->validateReason($data->reason ?? null);
        $date = $this->validator->validateDate($data->date ?? null);

        // 5. Handle contribution_percentage (optional)
        $contributionPercentage = $this->validator->validateContributionPercentage($data->contribution_percentage ?? null);
        if ($contributionPercentage === null) {
            $contributionPercentage = (int) $member['contribution_percentage'];
        }

        // 6. Create income record
        $incomeId = $this->incomeRepository->create($ownerId, $date, $reason, $amount, $contributionPercentage);

        // 7. Fetch created record
        $income = $this->incomeRepository->findById($incomeId);

        // 8. Return 201 Created
        $this->json($income, 201);
    }

    public function read(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->getAuthUser();
        $communityId = (int) $authUser->cid;

        // 2. Fetch income record
        $income = $this->incomeRepository->findById((int) $id);

        if (!$income) {
            throw AppException::INCOME_NOT_FOUND();
        }

        // 3. Verify community access (fetch owner's community)
        $ownerCommunityId = $this->memberRepository->getCommunityId((int) $income['owner_id']);

        if ($ownerCommunityId !== $communityId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Return record
        $this->json($income);
    }

    public function update(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->getAuthUser();
        $memberId = (int) $authUser->sub;

        // 2. Fetch income record
        $income = $this->incomeRepository->findById((int) $id);

        if (!$income) {
            throw AppException::INCOME_NOT_FOUND();
        }

        // 3. Verify ownership
        if ((int) $income['owner_id'] !== $memberId) {
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
        if (isset($data->contribution_percentage)) {
            $updates['contribution_percentage'] = $this->validator->validateContributionPercentage($data->contribution_percentage);
        }

        // 5. Execute update
        $this->incomeRepository->update((int) $id, $updates);

        // 6. Fetch updated record
        $updated = $this->incomeRepository->findById((int) $id);

        // 7. Return updated record
        $this->json($updated);
    }

    public function delete(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->getAuthUser();
        $memberId = (int) $authUser->sub;

        // 2. Fetch income record
        $income = $this->incomeRepository->findById((int) $id);

        if (!$income) {
            throw AppException::INCOME_NOT_FOUND();
        }

        // 3. Verify ownership
        if ((int) $income['owner_id'] !== $memberId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Delete record
        $this->incomeRepository->delete((int) $id);

        // 5. Return 204 No Content
        $this->json(null, 204);
    }
}

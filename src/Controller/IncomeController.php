<?php

namespace App\Controller;

use App\Dto\IncomeCreateDto;
use App\Dto\IncomeUpdateDto;
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

        // 3. Validate input via DTO
        $dto = IncomeCreateDto::createFromRequest($this->app->request());

        // 4. Handle contribution_percentage default
        $contributionPercentage = $dto->contribution_percentage ?? (int) $member['contribution_percentage'];

        // 5. Create income record
        $incomeId = $this->incomeRepository->create($ownerId, $dto->date, $dto->reason, $dto->amount, $contributionPercentage);

        // 6. Fetch created record
        $income = $this->incomeRepository->findById($incomeId);

        // 7. Return 201 Created
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

        // 4. Validate input via DTO
        $dto = IncomeUpdateDto::createFromRequest($this->app->request());

        // 5. Build updates array from non-null DTO properties
        $updates = [];
        if ($dto->amount !== null) {
            $updates['amount'] = $dto->amount;
        }
        if ($dto->reason !== null) {
            $updates['reason'] = $dto->reason;
        }
        if ($dto->date !== null) {
            $updates['date'] = $dto->date;
        }
        if ($dto->contribution_percentage !== null) {
            $updates['contribution_percentage'] = $dto->contribution_percentage;
        }

        // 6. Execute update
        $this->incomeRepository->update((int) $id, $updates);

        // 7. Fetch updated record
        $updated = $this->incomeRepository->findById((int) $id);

        // 8. Return updated record
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

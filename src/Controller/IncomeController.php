<?php

namespace App\Controller;

use App\Dto\IncomeDto;
use App\Exception\AppException;
use App\Repository\IncomeRepository;
use App\Repository\MemberRepository;
use App\Validation\Validator;
use flight\Engine;

class IncomeController extends Controller
{
    private MemberRepository $memberRepository;
    private IncomeRepository $incomeRepository;

    public function __construct(Engine $app)
    {
        parent::__construct($app);
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
        $dto = IncomeDto::createFromRequest($this->app->request());

        // 4. Handle contribution_percentage default
        $contributionPercentage = $dto->contribution_percentage ?? (int) $member['contribution_percentage'];

        // 5. Create income record
        $incomeId = $this->incomeRepository->create($ownerId, $dto, $contributionPercentage);

        // 6. Set Location header per RFC 9110
        $this->app->response()->header('Location', "/income/$incomeId");

        // 7. Return 201 Created with resource identifier
        $this->json(['id' => $incomeId], 201);
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
        $dto = IncomeDto::createFromRequest($this->app->request());

        // 5. Handle contribution_percentage default
        $contributionPercentage = $dto->contribution_percentage ?? (int) $income['contribution_percentage'];

        // 6. Execute update
        $this->incomeRepository->update((int) $id, $dto, $contributionPercentage);

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

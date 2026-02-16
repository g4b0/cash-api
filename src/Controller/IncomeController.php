<?php

namespace App\Controller;

use App\Dto\IncomeDto;
use App\Exception\AppException;
use App\Repository\IncomeRepository;
use App\Repository\MemberRepository;
use App\Response\CreatedResourceResponse;
use App\Response\IncomeResponse;
use App\Response\NoContentResponse;
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
        // 1. Get authenticated user (JWT is signed and trusted)
        $authUser = $this->getAuthUser();
        $memberId = (int) $authUser->sub;

        // 2. Validate input via DTO
        $dto = IncomeDto::createFromRequest($this->app->request());

        // 3. Handle contribution_percentage default (fetch only if needed)
        $contributionPercentage = $dto->contributionPercentage
            ?? $this->memberRepository->getContributionPercentage($memberId);

        // 4. Create income record
        $incomeId = $this->incomeRepository->create($memberId, $dto, $contributionPercentage);

        // 5. Return 201 Created with resource identifier (Location header set automatically)
        $this->json(new CreatedResourceResponse($incomeId, 'income'));
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
        $ownerCommunityId = $this->memberRepository->getCommunityId((int) $income['memberId']);

        if ($ownerCommunityId !== $communityId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Return record
        $this->json(new IncomeResponse($income));
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
        if ((int) $income['memberId'] !== $memberId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Validate input via DTO
        $dto = IncomeDto::createFromRequest($this->app->request());

        // 5. Handle contribution_percentage default
        $contributionPercentage = $dto->contributionPercentage ?? (int) $income['contributionPercentage'];

        // 6. Execute update
        $this->incomeRepository->update((int) $id, $dto, $contributionPercentage);

        // 7. Fetch updated record
        $updated = $this->incomeRepository->findById((int) $id);

        // 8. Return updated record
        $this->json(new IncomeResponse($updated));
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
        if ((int) $income['memberId'] !== $memberId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Delete record
        $this->incomeRepository->delete((int) $id);

        // 5. Return 204 No Content
        $this->json(new NoContentResponse());
    }
}

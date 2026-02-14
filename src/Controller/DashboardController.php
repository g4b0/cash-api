<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Repository\MemberRepository;
use App\Service\BalanceCalculator;
use flight\Engine;

class DashboardController extends Controller
{
    private MemberRepository $memberRepository;

    public function __construct(Engine $app)
    {
        parent::__construct($app);
        $this->memberRepository = new MemberRepository($this->getDb());
    }

    public function balance(string $community_id, string $member_id): void
    {
        // Get authenticated user
        $authUser = $this->getAuthUser();
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

        // Calculate and return balance
        $calculator = new BalanceCalculator($this->getDb());
        $balance = $calculator->calculate((int) $member_id);

        $this->json(['balance' => $balance]);
    }
}

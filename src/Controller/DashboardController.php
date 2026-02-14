<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Repository\MemberRepository;
use App\Service\BalanceCalculator;
use flight\Engine;

class DashboardController
{
    private Engine $app;
    private MemberRepository $memberRepository;

    public function __construct(Engine $app)
    {
        $this->app = $app;
        $this->memberRepository = new MemberRepository($app->get('db'));
    }

    public function balance(string $community_id, string $member_id): void
    {
        // Get authenticated user
        $authUser = $this->app->get('auth_user');
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
        $db = $this->app->get('db');
        $calculator = new BalanceCalculator($db);
        $balance = $calculator->calculate((int) $member_id);

        $this->app->json(['balance' => $balance]);
    }
}

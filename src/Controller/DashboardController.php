<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Service\BalanceCalculator;
use flight\Engine;
use PDO;

class DashboardController
{
    private Engine $app;

    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    public function balance(string $community_id, string $member_id): void
    {
        // Get authenticated user
        $authUser = $this->app->get('auth_user');
        $authCommunityId = (int) $authUser->cid;

        // Verify requested member exists and belongs to a community
        $db = $this->app->get('db');
        $stmt = $db->prepare('SELECT community_id FROM member WHERE id = ?');
        $stmt->execute([$member_id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            throw AppException::MEMBER_NOT_FOUND();
        }

        // Verify auth user is from the same community as requested member
        if ((int) $member['community_id'] !== $authCommunityId) {
            throw AppException::FORBIDDEN();
        }

        // Calculate and return balance
        $calculator = new BalanceCalculator($db);
        $balance = $calculator->calculate((int) $member_id);

        $this->app->json(['balance' => $balance]);
    }
}

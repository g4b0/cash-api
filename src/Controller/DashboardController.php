<?php

namespace App\Controller;

use App\Service\BalanceCalculator;
use flight\Engine;

class DashboardController
{
    private Engine $app;

    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    public function show(string $community_id, string $member_id): void
    {
        $db = $this->app->get('db');
        $calculator = new BalanceCalculator($db);
        $balance = $calculator->calculate((int) $member_id);

        $this->app->json(['balance' => $balance]);
    }
}

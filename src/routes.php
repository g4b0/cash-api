<?php

use App\Controller\DashboardController;
use flight\Engine;

function registerRoutes(Engine $app): void
{
    $app->route('GET /@community_id/@member_id', [DashboardController::class, 'show']);
}

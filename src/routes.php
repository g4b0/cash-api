<?php

use flight\Engine;

function registerRoutes(Engine $app): void
{
    $app->route('GET /@community_id/@member_id', function (string $community_id, string $member_id) use ($app) {
        $app->json([]);
    });
}

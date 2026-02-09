<?php

require __DIR__ . '/vendor/autoload.php';

$app = new flight\Engine();

$pdo = new PDO('sqlite:' . __DIR__ . '/database/cash.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$app->set('db', $pdo);
$app->set('jwt_secret', getenv('JWT_SECRET') ?: 'change-me-in-production');

registerRoutes($app);
$app->start();

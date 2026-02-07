<?php

require __DIR__ . '/vendor/autoload.php';

$app = new flight\Engine();
registerRoutes($app);
$app->start();

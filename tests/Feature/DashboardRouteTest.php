<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use flight\Engine;

class DashboardRouteTest extends TestCase
{
    private Engine $app;

    protected function setUp(): void
    {
        putenv('PHPUNIT_TEST=1');

        $this->app = new Engine();
        registerRoutes($this->app);
    }

    public function testDashboardRouteReturns200(): void
    {
        $this->app->request()->url = '/1/1';
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());
    }
}

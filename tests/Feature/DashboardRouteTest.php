<?php

namespace Tests\Feature;

use App\Service\JwtService;
use PDO;
use PHPUnit\Framework\TestCase;
use flight\Engine;
use Tests\Support\DatabaseSeeder;

class DashboardRouteTest extends TestCase
{
    private Engine $app;
    private PDO $db;

    protected function setUp(): void
    {
        putenv('PHPUNIT_TEST=1');

        $this->db = DatabaseSeeder::createDatabase();
        $this->app = new Engine();
        $this->app->set('db', $this->db);
        $this->app->set('jwt_secret', 'test-secret');

        registerRoutes($this->app);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    private function authenticateAs(int $memberId, int $communityId): void
    {
        $jwtService = new JwtService('test-secret');
        $token = $jwtService->generateAccessToken($memberId, $communityId);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    }

    public function testDashboardWithoutTokenReturns401(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'Test User', 'test', 75);

        $this->app->request()->url = "/$communityId/$memberId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testDashboardWithInvalidTokenReturns401(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'Test User', 'test', 75);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid-token';

        $this->app->request()->url = "/$communityId/$memberId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testDashboardReturnsZeroBalanceForMemberWithNoRecords(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'Test User', 'test', 75);

        $this->authenticateAs($memberId, $communityId);

        $this->app->request()->url = "/$communityId/$memberId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals(0, $body['balance']);
    }

    public function testDashboardReturnsCorrectBalance(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'Test User', 'test', 75);

        // Incomes: 1000 * 75% = 750, 500 * 75% = 375 → total contributions = 1125
        DatabaseSeeder::seedIncome($this->db, $memberId, '2025-01-15', 'Salary', 1000.00, 75);
        DatabaseSeeder::seedIncome($this->db, $memberId, '2025-02-15', 'Bonus', 500.00, 75);

        // Expenses: 300 + 200 = 500
        DatabaseSeeder::seedExpense($this->db, $memberId, '2025-01-20', 'Groceries', 300.00);
        DatabaseSeeder::seedExpense($this->db, $memberId, '2025-02-20', 'Utilities', 200.00);

        $this->authenticateAs($memberId, $communityId);

        // Balance = contributions - expenses = 1125 - 500 = 625
        $this->app->request()->url = "/$communityId/$memberId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals(625, $body['balance']);
    }
}

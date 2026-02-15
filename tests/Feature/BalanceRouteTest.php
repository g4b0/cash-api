<?php

namespace Tests\Feature;

use App\Service\JwtService;
use PDO;
use PHPUnit\Framework\TestCase;
use flight\Engine;
use Tests\Support\DatabaseSeeder;

class BalanceRouteTest extends TestCase
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

    public function testBalanceWithoutTokenReturns401(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'Test User', 'test', 75);

        $this->app->request()->url = "/balance/$communityId/$memberId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testBalanceWithInvalidTokenReturns401(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'Test User', 'test', 75);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid-token';

        $this->app->request()->url = "/balance/$communityId/$memberId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testBalanceReturnsZeroBalanceForMemberWithNoRecords(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'Test User', 'test', 75);

        $this->authenticateAs($memberId, $communityId);

        $this->app->request()->url = "/balance/$communityId/$memberId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals($memberId, $body['memberId']);
        $this->assertEquals('0', $body['balance']);
    }

    public function testBalanceReturnsCorrectBalance(): void
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
        $this->app->request()->url = "/balance/$communityId/$memberId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals($memberId, $body['memberId']);
        $this->assertEquals('625', $body['balance']);
    }

    public function testBalanceForMemberInSameCommunityReturns200(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        $member1Id = DatabaseSeeder::seedMember($this->db, $communityId, 'Member 1', 'member1', 75);
        $member2Id = DatabaseSeeder::seedMember($this->db, $communityId, 'Member 2', 'member2', 80);

        // Authenticate as member1, view member2's balance
        $this->authenticateAs($member1Id, $communityId);

        $this->app->request()->url = "/balance/$communityId/$member2Id";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('memberId', $body);
        $this->assertArrayHasKey('balance', $body);
        $this->assertEquals($member2Id, $body['memberId']);
    }

    public function testBalanceForMemberInDifferentCommunityReturns403(): void
    {
        $community1Id = DatabaseSeeder::seedCommunity($this->db, 'Community 1');
        $member1Id = DatabaseSeeder::seedMember($this->db, $community1Id, 'Member 1', 'member1', 75);

        $community2Id = DatabaseSeeder::seedCommunity($this->db, 'Community 2');
        $member2Id = DatabaseSeeder::seedMember($this->db, $community2Id, 'Member 2', 'member2', 80);

        // Authenticate as member1, try to view member2's balance (different community)
        $this->authenticateAs($member1Id, $community1Id);

        $this->app->request()->url = "/balance/$community2Id/$member2Id";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(403, $this->app->response()->status());
    }

    public function testBalanceForNonExistentMemberReturns404(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'Test User', 'test', 75);

        $this->authenticateAs($memberId, $communityId);

        $this->app->request()->url = "/balance/$communityId/99999";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(404, $this->app->response()->status());
    }
}

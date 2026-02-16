<?php

namespace Tests\Feature;

use App\Service\JwtService;
use PDO;
use PHPUnit\Framework\TestCase;
use flight\Engine;
use Tests\Support\DatabaseSeeder;

class TransactionsRouteTest extends TestCase
{
    private Engine $app;
    private PDO $db;
    private int $communityId;
    private int $memberId;

    protected function setUp(): void
    {
        putenv('PHPUNIT_TEST=1');

        $this->db = DatabaseSeeder::createDatabase();
        $this->app = new Engine();
        $this->app->set('db', $this->db);
        $this->app->set('jwt_secret', 'test-secret');

        registerRoutes($this->app);

        $this->communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
        $this->memberId = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Test User', 'testuser', 75);
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

    public function testListWithoutAuthReturns401(): void
    {
        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testListReturnsEmptyArrayForMemberWithNoTransactions(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertIsArray($body['transactions']);
        $this->assertEmpty($body['transactions']);
        $this->assertEquals(1, $body['pagination']['currentPage']);
        $this->assertEquals(0, $body['pagination']['totalPages']);
        $this->assertEquals(0, $body['pagination']['totalItems']);
        $this->assertEquals(25, $body['pagination']['perPage']);
    }

    public function testListReturnsMergedTransactionsSortedByDate(): void
    {
        // Seed transactions with specific dates
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);
        DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-15', 'Groceries', 500.00);
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-13', 'Bonus', 200.00, 75);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertCount(3, $body['transactions']);

        // Verify sorted by date DESC
        $this->assertEquals('2025-02-15', $body['transactions'][0]['date']); // Groceries (expense)
        $this->assertEquals('expense', $body['transactions'][0]['type']);
        $this->assertEquals('2025-02-14', $body['transactions'][1]['date']); // Salary (income)
        $this->assertEquals('income', $body['transactions'][1]['type']);
        $this->assertEquals('2025-02-13', $body['transactions'][2]['date']); // Bonus (income)
        $this->assertEquals('income', $body['transactions'][2]['type']);
    }

    public function testListIncludesContributionPercentageForIncome(): void
    {
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 80);
        DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-15', 'Groceries', 500.00);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $body = json_decode($this->app->response()->getBody(), true);

        // Expense should NOT have contributionPercentage field
        $this->assertEquals('expense', $body['transactions'][0]['type']);
        $this->assertArrayNotHasKey('contributionPercentage', $body['transactions'][0]);

        // Income should have contribution_percentage
        $this->assertEquals('income', $body['transactions'][1]['type']);
        $this->assertEquals(80, $body['transactions'][1]['contributionPercentage']);
    }

    public function testListDefaultsToPageOneWith25Items(): void
    {
        // Seed 30 transactions
        for ($i = 1; $i <= 30; $i++) {
            DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', "Income $i", 100.00, 75);
        }

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $body = json_decode($this->app->response()->getBody(), true);

        $this->assertCount(25, $body['transactions']); // First page has 25 items
        $this->assertEquals(1, $body['pagination']['currentPage']);
        $this->assertEquals(2, $body['pagination']['totalPages']);
        $this->assertEquals(30, $body['pagination']['totalItems']);
        $this->assertEquals(25, $body['pagination']['perPage']);
    }

    public function testListReturnsSecondPage(): void
    {
        // Seed 30 transactions
        for ($i = 1; $i <= 30; $i++) {
            DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', "Income $i", 100.00, 75);
        }

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}/25/2";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $body = json_decode($this->app->response()->getBody(), true);

        $this->assertCount(5, $body['transactions']); // Second page has remaining 5 items
        $this->assertEquals(2, $body['pagination']['currentPage']);
        $this->assertEquals(2, $body['pagination']['totalPages']);
        $this->assertEquals(30, $body['pagination']['totalItems']);
        $this->assertEquals(25, $body['pagination']['perPage']);
    }

    public function testListWithCustomPerPage(): void
    {
        // Seed 30 transactions
        for ($i = 1; $i <= 30; $i++) {
            DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', "Income $i", 100.00, 75);
        }

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}/10";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $body = json_decode($this->app->response()->getBody(), true);

        $this->assertCount(10, $body['transactions']);
        $this->assertEquals(1, $body['pagination']['currentPage']);
        $this->assertEquals(3, $body['pagination']['totalPages']); // 30 items / 10 per page = 3 pages
        $this->assertEquals(30, $body['pagination']['totalItems']);
        $this->assertEquals(10, $body['pagination']['perPage']);
    }

    public function testListWithPerPage15ReturnsCorrectPaginationMetadata(): void
    {
        // Seed 30 transactions
        for ($i = 1; $i <= 30; $i++) {
            DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', "Income $i", 100.00, 75);
        }

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}/15";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $body = json_decode($this->app->response()->getBody(), true);

        $this->assertCount(15, $body['transactions']);
        $this->assertEquals(1, $body['pagination']['currentPage']);
        $this->assertEquals(2, $body['pagination']['totalPages']); // 30 items / 15 per page = 2 pages
        $this->assertEquals(30, $body['pagination']['totalItems']);
        $this->assertEquals(15, $body['pagination']['perPage']); // Bug: this might be showing 25
    }

    public function testListForMemberInSameCommunityReturns200(): void
    {
        $member2Id = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Member 2', 'member2', 80);

        // Authenticate as member1, view member2's transactions
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$member2Id}";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());
    }

    public function testListForMemberInDifferentCommunityReturns403(): void
    {
        $community2Id = DatabaseSeeder::seedCommunity($this->db, 'Community 2');
        $member2Id = DatabaseSeeder::seedMember($this->db, $community2Id, 'Member 2', 'member2', 80);

        // Authenticate as member1, try to view member2's transactions (different community)
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$community2Id}/{$member2Id}";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(403, $this->app->response()->status());
    }

    public function testListForNonExistentMemberReturns404(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/99999";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(404, $this->app->response()->status());
    }

    public function testListWithInvalidPerPageReturns400(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}/0";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testListWithInvalidPageReturns400(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}/25/0";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testListWithPageBeyondRangeReturnsEmptyData(): void
    {
        // Seed only 5 transactions (1 page with default per_page=25)
        for ($i = 1; $i <= 5; $i++) {
            DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', "Income $i", 100.00, 75);
        }

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/transactions/{$this->communityId}/{$this->memberId}/25/999";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEmpty($body['transactions']); // Empty array
        $this->assertEquals(999, $body['pagination']['currentPage']); // Still reflects requested page
        $this->assertEquals(1, $body['pagination']['totalPages']); // But shows actual total pages
        $this->assertEquals(5, $body['pagination']['totalItems']);
    }
}

<?php

namespace Tests\Feature;

use App\Service\JwtService;
use flight\Engine;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseSeeder;

require_once __DIR__ . '/../../src/routes.php';

class ExpenseRouteTest extends TestCase
{
    private Engine $app;
    private PDO $db;
    private int $communityId;
    private int $memberId;
    private int $otherCommunityId;
    private int $otherMemberId;

    protected function setUp(): void
    {
        putenv('PHPUNIT_TEST=1');

        $this->db = DatabaseSeeder::createDatabase();

        $this->app = new Engine();
        $this->app->set('db', $this->db);
        $this->app->set('jwt_secret', 'test-secret');

        registerRoutes($this->app);

        // Seed test data
        $this->communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
        $this->memberId = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Test User', 'testuser', 75);

        // Seed another community for authorization tests
        $this->otherCommunityId = DatabaseSeeder::seedCommunity($this->db, 'Other Community');
        $this->otherMemberId = DatabaseSeeder::seedMember($this->db, $this->otherCommunityId, 'Other User', 'otheruser', 80);
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

    // POST /expense tests
    public function testCreateExpenseWithoutAuthReturns401(): void
    {
        $this->app->request()->url = '/expense';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 500,
            'reason' => 'Groceries',
        ]);

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());
    }

    public function testCreateExpenseWithValidData(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/expense';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 500.75,
            'reason' => 'Groceries',
            'date' => '2025-02-14',
        ]);

        $this->app->start();

        $this->assertEquals(201, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('id', $body);
        $this->assertIsInt($body['id']);
        $this->assertGreaterThan(0, $body['id']);

        // Verify Location header per RFC 9110
        $locationHeader = $this->app->response()->getHeader('Location');
        $this->assertEquals("/expense/{$body['id']}", $locationHeader);
    }

    public function testCreateExpenseWithoutAmountReturns400(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/expense';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'reason' => 'Groceries',
        ]);

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());
        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertStringContainsString('greater than zero', $body['error']);
    }

    public function testCreateExpenseWithZeroAmountReturns400(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/expense';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 0,
            'reason' => 'Groceries',
        ]);

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());
    }

    public function testCreateExpenseWithNegativeAmountReturns400(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/expense';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => -50,
            'reason' => 'Groceries',
        ]);

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());
    }

    public function testCreateExpenseWithoutReasonReturns400(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/expense';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 500,
        ]);

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());
        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertStringContainsString('Reason is required', $body['error']);
    }

    public function testCreateExpenseWithOptionalDateDefaultsToToday(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/expense';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 500,
            'reason' => 'Groceries',
        ]);

        $this->app->start();

        $this->assertEquals(201, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('id', $body);
    }

    // GET /expense/{id} tests
    public function testGetExpenseWithoutAuthReturns401(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500);

        $this->app->request()->url = "/expense/$expenseId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());
    }

    public function testGetExpenseReturnsOwnRecord(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/expense/$expenseId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals($expenseId, $body['id']);
        $this->assertEquals($this->memberId, $body['ownerId']);
        $this->assertEquals('Groceries', $body['reason']);
    }

    public function testGetExpenseReturnsRecordFromSameCommunity(): void
    {
        // Create another member in the same community
        $otherMemberInSameCommunity = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Another User', 'anotheruser', 80);
        $expenseId = DatabaseSeeder::seedExpense($this->db, $otherMemberInSameCommunity, '2025-02-14', 'Utilities', 200);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/expense/$expenseId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals($expenseId, $body['id']);
    }

    public function testGetExpenseFromDifferentCommunityReturns403(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->otherMemberId, '2025-02-14', 'Rent', 1000);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/expense/$expenseId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(403, $this->app->response()->status());
    }

    public function testGetNonExistentExpenseReturns404(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/expense/99999';
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(404, $this->app->response()->status());
    }

    // PUT /expense/{id} tests
    public function testUpdateExpenseWithoutAuthReturns401(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500);

        $this->app->request()->url = "/expense/$expenseId";
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 600,
            'reason' => 'Groceries',
            'date' => '2025-02-14',
        ]);

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());
    }

    public function testUpdateOwnExpenseSuccess(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/expense/$expenseId";
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 600,
            'reason' => 'Updated Groceries',
            'date' => '2025-02-15',
        ]);

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals('600', $body['amount']);
        $this->assertEquals('Updated Groceries', $body['reason']);
        $this->assertEquals('2025-02-15', $body['date']);
    }

    public function testUpdateOthersExpenseReturns403(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->otherMemberId, '2025-02-14', 'Rent', 1000);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/expense/$expenseId";
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 1200,
            'reason' => 'Rent',
            'date' => '2025-02-14',
        ]);

        $this->app->start();

        $this->assertEquals(403, $this->app->response()->status());
    }

    public function testUpdateNonExistentExpenseReturns404(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/expense/99999';
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 600,
            'reason' => 'Groceries',
            'date' => '2025-02-14',
        ]);

        $this->app->start();

        $this->assertEquals(404, $this->app->response()->status());
    }

    // DELETE /expense/{id} tests
    public function testDeleteExpenseWithoutAuthReturns401(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500);

        $this->app->request()->url = "/expense/$expenseId";
        $this->app->request()->method = 'DELETE';

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());
    }

    public function testDeleteOwnExpenseSuccess(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/expense/$expenseId";
        $this->app->request()->method = 'DELETE';

        $this->app->start();

        $this->assertEquals(204, $this->app->response()->status());

        // Verify record is deleted
        $stmt = $this->db->prepare('SELECT * FROM expense WHERE id = ?');
        $stmt->execute([$expenseId]);
        $this->assertFalse($stmt->fetch());
    }

    public function testDeleteOthersExpenseReturns403(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->otherMemberId, '2025-02-14', 'Rent', 1000);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/expense/$expenseId";
        $this->app->request()->method = 'DELETE';

        $this->app->start();

        $this->assertEquals(403, $this->app->response()->status());
    }

    public function testDeleteNonExistentExpenseReturns404(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/expense/99999';
        $this->app->request()->method = 'DELETE';

        $this->app->start();

        $this->assertEquals(404, $this->app->response()->status());
    }
}

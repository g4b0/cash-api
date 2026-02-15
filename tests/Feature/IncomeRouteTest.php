<?php

namespace Tests\Feature;

use App\Service\JwtService;
use flight\Engine;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseSeeder;

require_once __DIR__ . '/../../src/routes.php';

class IncomeRouteTest extends TestCase
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

    // POST /income tests
    public function testCreateIncomeWithoutAuthReturns401(): void
    {
        $this->app->request()->url = '/income';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 1000,
            'reason' => 'Salary',
        ]);

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());
    }

    public function testCreateIncomeWithValidData(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 1000.50,
            'reason' => 'Salary',
            'date' => '2025-02-14',
            'contributionPercentage' => 80,
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
        $this->assertEquals("/income/{$body['id']}", $locationHeader);
    }

    public function testCreateIncomeWithoutAmountReturns400(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'reason' => 'Salary',
        ]);

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());
        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertStringContainsString('greater than zero', $body['error']);
    }

    public function testCreateIncomeWithZeroAmountReturns400(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 0,
            'reason' => 'Salary',
        ]);

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());
    }

    public function testCreateIncomeWithNegativeAmountReturns400(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => -100,
            'reason' => 'Salary',
        ]);

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());
    }

    public function testCreateIncomeWithoutReasonReturns400(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 1000,
        ]);

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());
        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertStringContainsString('Reason is required', $body['error']);
    }

    public function testCreateIncomeWithOptionalDateDefaultsToToday(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 1000,
            'reason' => 'Salary',
        ]);

        $this->app->start();

        $this->assertEquals(201, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('id', $body);
    }

    public function testCreateIncomeWithOptionalContributionPercentageUsesDefault(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 1000,
            'reason' => 'Salary',
        ]);

        $this->app->start();

        $this->assertEquals(201, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('id', $body);
    }

    public function testCreateIncomeSetsOwnerIdFromJWT(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData([
            'amount' => 1000,
            'reason' => 'Salary',
        ]);

        $this->app->start();

        $this->assertEquals(201, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('id', $body);
    }

    // GET /income/{id} tests
    public function testGetIncomeWithoutAuthReturns401(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000, 75);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());
    }

    public function testGetIncomeReturnsOwnRecord(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000, 75);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals($incomeId, $body['id']);
        $this->assertEquals($this->memberId, $body['memberId']);
        $this->assertEquals('Salary', $body['reason']);
    }

    public function testGetIncomeReturnsRecordFromSameCommunity(): void
    {
        // Create another member in the same community
        $otherMemberInSameCommunity = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Another User', 'anotheruser', 80);
        $incomeId = DatabaseSeeder::seedIncome($this->db, $otherMemberInSameCommunity, '2025-02-14', 'Bonus', 500, 80);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals($incomeId, $body['id']);
    }

    public function testGetIncomeFromDifferentCommunityReturns403(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->otherMemberId, '2025-02-14', 'Salary', 2000, 80);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(403, $this->app->response()->status());
    }

    public function testGetNonExistentIncomeReturns404(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income/99999';
        $this->app->request()->method = 'GET';

        $this->app->start();

        $this->assertEquals(404, $this->app->response()->status());
    }

    // PUT /income/{id} tests
    public function testUpdateIncomeWithoutAuthReturns401(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000, 75);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 1500,
            'reason' => 'Salary',
            'date' => '2025-02-14',
        ]);

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());
    }

    public function testUpdateOwnIncomeSuccess(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000, 75);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 1500,
            'reason' => 'Updated Salary',
            'date' => '2025-02-15',
            'contributionPercentage' => 85,
        ]);

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals('1500', $body['amount']);
        $this->assertEquals('Updated Salary', $body['reason']);
        $this->assertEquals('2025-02-15', $body['date']);
        $this->assertEquals(85, $body['contributionPercentage']);
    }

    public function testUpdateIncomeReasonOnly(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000, 75);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 1000,
            'reason' => 'Bonus',
            'date' => '2025-02-14',
        ]);

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals('Bonus', $body['reason']);
        $this->assertEquals('1000', $body['amount']);
    }

    public function testUpdateIncomeAmountOnly(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000, 75);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 2000,
            'reason' => 'Salary',
            'date' => '2025-02-14',
        ]);

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals('2000', $body['amount']);
        $this->assertEquals('Salary', $body['reason']);
    }

    public function testUpdateIncomeDateOnly(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000, 75);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 1000,
            'reason' => 'Salary',
            'date' => '2025-03-01',
        ]);

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals('2025-03-01', $body['date']);
    }

    public function testUpdateIncomeContributionPercentageOnly(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000, 75);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 1000,
            'reason' => 'Salary',
            'date' => '2025-02-14',
            'contributionPercentage' => 90,
        ]);

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertEquals(90, $body['contributionPercentage']);
    }

    public function testUpdateOthersIncomeReturns403(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->otherMemberId, '2025-02-14', 'Salary', 2000, 80);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 3000,
            'reason' => 'Salary',
            'date' => '2025-02-14',
        ]);

        $this->app->start();

        $this->assertEquals(403, $this->app->response()->status());
    }

    public function testUpdateNonExistentIncomeReturns404(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income/99999';
        $this->app->request()->method = 'PUT';
        $this->app->request()->data->setData([
            'amount' => 1500,
            'reason' => 'Salary',
            'date' => '2025-02-14',
        ]);

        $this->app->start();

        $this->assertEquals(404, $this->app->response()->status());
    }

    // DELETE /income/{id} tests
    public function testDeleteIncomeWithoutAuthReturns401(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000, 75);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'DELETE';

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());
    }

    public function testDeleteOwnIncomeSuccess(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000, 75);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'DELETE';

        $this->app->start();

        $this->assertEquals(204, $this->app->response()->status());

        // Verify record is deleted
        $stmt = $this->db->prepare('SELECT * FROM income WHERE id = ?');
        $stmt->execute([$incomeId]);
        $this->assertFalse($stmt->fetch());
    }

    public function testDeleteOthersIncomeReturns403(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->otherMemberId, '2025-02-14', 'Salary', 2000, 80);

        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = "/income/$incomeId";
        $this->app->request()->method = 'DELETE';

        $this->app->start();

        $this->assertEquals(403, $this->app->response()->status());
    }

    public function testDeleteNonExistentIncomeReturns404(): void
    {
        $this->authenticateAs($this->memberId, $this->communityId);

        $this->app->request()->url = '/income/99999';
        $this->app->request()->method = 'DELETE';

        $this->app->start();

        $this->assertEquals(404, $this->app->response()->status());
    }
}

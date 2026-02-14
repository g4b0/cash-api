<?php

namespace Tests\Unit\Repository;

use App\Repository\IncomeRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseSeeder;

class IncomeRepositoryTest extends TestCase
{
    private PDO $db;
    private IncomeRepository $repository;
    private int $communityId;
    private int $memberId;

    protected function setUp(): void
    {
        $this->db = DatabaseSeeder::createDatabase();
        $this->repository = new IncomeRepository($this->db);

        $this->communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
        $this->memberId = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Test User', 'testuser', 75);
    }

    public function testFindByIdReturnsArrayWhenIncomeExists(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);

        $result = $this->repository->findById($incomeId);

        $this->assertIsArray($result);
        $this->assertEquals($incomeId, $result['id']);
        $this->assertEquals($this->memberId, $result['owner_id']);
        $this->assertEquals('2025-02-14', $result['date']);
        $this->assertEquals('Salary', $result['reason']);
        $this->assertEquals('1000', $result['amount']);
        $this->assertEquals(75, $result['contribution_percentage']);
    }

    public function testFindByIdReturnsNullWhenIncomeDoesNotExist(): void
    {
        $result = $this->repository->findById(99999);

        $this->assertNull($result);
    }

    public function testCreateReturnsIncomeId(): void
    {
        $incomeId = $this->repository->create($this->memberId, '2025-02-14', 'Bonus', 500.50, 80);

        $this->assertIsInt($incomeId);
        $this->assertGreaterThan(0, $incomeId);

        // Verify it was actually created
        $income = $this->repository->findById($incomeId);
        $this->assertNotNull($income);
        $this->assertEquals($this->memberId, $income['owner_id']);
        $this->assertEquals('Bonus', $income['reason']);
        $this->assertEquals('500.5', $income['amount']);
        $this->assertEquals(80, $income['contribution_percentage']);
    }

    public function testUpdateModifiesFields(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);

        $result = $this->repository->update($incomeId, [
            'reason' => 'Updated Salary',
            'amount' => 1500.00,
            'contribution_percentage' => 85,
        ]);

        $this->assertTrue($result);

        $income = $this->repository->findById($incomeId);
        $this->assertEquals('Updated Salary', $income['reason']);
        $this->assertEquals('1500', $income['amount']);
        $this->assertEquals(85, $income['contribution_percentage']);
    }

    public function testUpdateWithEmptyFieldsReturnsTrue(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);

        $result = $this->repository->update($incomeId, []);

        $this->assertTrue($result);
    }

    public function testDeleteRemovesIncome(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);

        $result = $this->repository->delete($incomeId);

        $this->assertTrue($result);

        $income = $this->repository->findById($incomeId);
        $this->assertNull($income);
    }

    public function testDeleteReturnsTrueEvenIfIncomeDoesNotExist(): void
    {
        $result = $this->repository->delete(99999);

        $this->assertTrue($result);
    }
}

<?php

namespace Tests\Unit\Repository;

use App\Dto\IncomeCreateDto;
use App\Dto\IncomeUpdateDto;
use App\Repository\IncomeRepository;
use flight\net\Request;
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
        $request = new Request();
        $request->data->setData([
            'amount' => 500.50,
            'reason' => 'Bonus',
            'date' => '2025-02-14'
        ]);
        $dto = IncomeCreateDto::createFromRequest($request);

        $incomeId = $this->repository->create($this->memberId, $dto, 80);

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

        $request = new Request();
        $request->data->setData([
            'reason' => 'Updated Salary',
            'amount' => 1500.00,
            'contribution_percentage' => 85,
        ]);
        $dto = IncomeUpdateDto::createFromRequest($request);

        $result = $this->repository->update($incomeId, $dto);

        $this->assertTrue($result);

        $income = $this->repository->findById($incomeId);
        $this->assertEquals('Updated Salary', $income['reason']);
        $this->assertEquals('1500', $income['amount']);
        $this->assertEquals(85, $income['contribution_percentage']);
    }

    public function testUpdateWithEmptyFieldsReturnsTrue(): void
    {
        $incomeId = DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);

        $request = new Request();
        $request->data->setData([]);
        $dto = IncomeUpdateDto::createFromRequest($request);

        $result = $this->repository->update($incomeId, $dto);

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

    public function testCalculateTotalContributionsReturnsZeroWhenNoIncome(): void
    {
        $result = $this->repository->calculateTotalContributions($this->memberId);

        $this->assertEquals(0.0, $result);
    }

    public function testCalculateTotalContributionsReturnsCorrectSum(): void
    {
        // Income 1: 1000 * 75% = 750
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);
        // Income 2: 500 * 80% = 400
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-15', 'Bonus', 500.00, 80);

        $result = $this->repository->calculateTotalContributions($this->memberId);

        // Total contributions = 750 + 400 = 1150
        $this->assertEquals(1150.0, $result);
    }

    public function testCalculateTotalContributionsOnlyIncludesSpecificMember(): void
    {
        $member2Id = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Member 2', 'member2', 80);

        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);
        DatabaseSeeder::seedIncome($this->db, $member2Id, '2025-02-14', 'Other Salary', 2000.00, 80);

        $result = $this->repository->calculateTotalContributions($this->memberId);

        // Only member1's income: 1000 * 75% = 750
        $this->assertEquals(750.0, $result);
    }
}

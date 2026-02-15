<?php

namespace Tests\Unit\Repository;

use App\Repository\TransactionRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseSeeder;

class TransactionRepositoryTest extends TestCase
{
    private PDO $db;
    private TransactionRepository $repository;
    private int $communityId;
    private int $memberId;

    protected function setUp(): void
    {
        $this->db = DatabaseSeeder::createDatabase();
        $this->repository = new TransactionRepository($this->db);

        $this->communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
        $this->memberId = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Test User', 'testuser', 75);
    }

    public function testCountByMemberIdReturnsZeroWhenNoTransactions(): void
    {
        $result = $this->repository->countByMemberId($this->memberId);

        $this->assertEquals(0, $result);
    }

    public function testCountByMemberIdReturnsCorrectCount(): void
    {
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-15', 'Bonus', 500.00, 75);
        DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-16', 'Groceries', 300.00);

        $result = $this->repository->countByMemberId($this->memberId);

        $this->assertEquals(3, $result);
    }

    public function testFindPaginatedByMemberIdReturnsEmptyArrayWhenNoTransactions(): void
    {
        $result = $this->repository->findPaginatedByMemberId($this->memberId, 25, 0);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindPaginatedByMemberIdReturnsMergedTransactions(): void
    {
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);
        DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-15', 'Groceries', 500.00);

        $result = $this->repository->findPaginatedByMemberId($this->memberId, 25, 0);

        $this->assertCount(2, $result);
    }

    public function testFindPaginatedByMemberIdSortsByDateDesc(): void
    {
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);
        DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-15', 'Groceries', 500.00);
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-13', 'Bonus', 200.00, 75);

        $result = $this->repository->findPaginatedByMemberId($this->memberId, 25, 0);

        // Should be sorted by date DESC
        $this->assertEquals('2025-02-15', $result[0]['date']); // Groceries (expense)
        $this->assertEquals('2025-02-14', $result[1]['date']); // Salary (income)
        $this->assertEquals('2025-02-13', $result[2]['date']); // Bonus (income)
    }

    public function testFindPaginatedByMemberIdIncludesTypeField(): void
    {
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 75);
        DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-15', 'Groceries', 500.00);

        $result = $this->repository->findPaginatedByMemberId($this->memberId, 25, 0);

        $this->assertEquals('expense', $result[0]['type']);
        $this->assertEquals('income', $result[1]['type']);
    }

    public function testFindPaginatedByMemberIdIncludesContributionPercentageForIncome(): void
    {
        DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', 'Salary', 1000.00, 80);
        DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-15', 'Groceries', 500.00);

        $result = $this->repository->findPaginatedByMemberId($this->memberId, 25, 0);

        // Expense should have null contribution_percentage
        $this->assertNull($result[0]['contributionPercentage']);
        // Income should have contribution_percentage
        $this->assertEquals(80, $result[1]['contributionPercentage']);
    }

    public function testFindPaginatedByMemberIdRespectsLimitAndOffset(): void
    {
        // Create 30 transactions
        for ($i = 1; $i <= 30; $i++) {
            DatabaseSeeder::seedIncome($this->db, $this->memberId, '2025-02-14', "Income $i", 100.00, 75);
        }

        // First page: 25 items
        $page1 = $this->repository->findPaginatedByMemberId($this->memberId, 25, 0);
        $this->assertCount(25, $page1);

        // Second page: 5 items
        $page2 = $this->repository->findPaginatedByMemberId($this->memberId, 25, 25);
        $this->assertCount(5, $page2);
    }
}

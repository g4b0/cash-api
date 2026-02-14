<?php

namespace Tests\Unit\Repository;

use App\Repository\ExpenseRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseSeeder;

class ExpenseRepositoryTest extends TestCase
{
    private PDO $db;
    private ExpenseRepository $repository;
    private int $communityId;
    private int $memberId;

    protected function setUp(): void
    {
        $this->db = DatabaseSeeder::createDatabase();
        $this->repository = new ExpenseRepository($this->db);

        $this->communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
        $this->memberId = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Test User', 'testuser', 75);
    }

    public function testFindByIdReturnsArrayWhenExpenseExists(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500.00);

        $result = $this->repository->findById($expenseId);

        $this->assertIsArray($result);
        $this->assertEquals($expenseId, $result['id']);
        $this->assertEquals($this->memberId, $result['owner_id']);
        $this->assertEquals('2025-02-14', $result['date']);
        $this->assertEquals('Groceries', $result['reason']);
        $this->assertEquals('500', $result['amount']);
    }

    public function testFindByIdReturnsNullWhenExpenseDoesNotExist(): void
    {
        $result = $this->repository->findById(99999);

        $this->assertNull($result);
    }

    public function testCreateReturnsExpenseId(): void
    {
        $expenseId = $this->repository->create($this->memberId, '2025-02-14', 'Utilities', 200.75);

        $this->assertIsInt($expenseId);
        $this->assertGreaterThan(0, $expenseId);

        // Verify it was actually created
        $expense = $this->repository->findById($expenseId);
        $this->assertNotNull($expense);
        $this->assertEquals($this->memberId, $expense['owner_id']);
        $this->assertEquals('Utilities', $expense['reason']);
        $this->assertEquals('200.75', $expense['amount']);
    }

    public function testUpdateModifiesFields(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500.00);

        $result = $this->repository->update($expenseId, [
            'reason' => 'Updated Groceries',
            'amount' => 600.00,
        ]);

        $this->assertTrue($result);

        $expense = $this->repository->findById($expenseId);
        $this->assertEquals('Updated Groceries', $expense['reason']);
        $this->assertEquals('600', $expense['amount']);
    }

    public function testUpdateWithEmptyFieldsReturnsTrue(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500.00);

        $result = $this->repository->update($expenseId, []);

        $this->assertTrue($result);
    }

    public function testDeleteRemovesExpense(): void
    {
        $expenseId = DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500.00);

        $result = $this->repository->delete($expenseId);

        $this->assertTrue($result);

        $expense = $this->repository->findById($expenseId);
        $this->assertNull($expense);
    }

    public function testDeleteReturnsTrueEvenIfExpenseDoesNotExist(): void
    {
        $result = $this->repository->delete(99999);

        $this->assertTrue($result);
    }

    public function testCalculateTotalExpensesReturnsZeroWhenNoExpenses(): void
    {
        $result = $this->repository->calculateTotalExpenses($this->memberId);

        $this->assertEquals(0.0, $result);
    }

    public function testCalculateTotalExpensesReturnsCorrectSum(): void
    {
        DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500.00);
        DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-15', 'Utilities', 300.00);

        $result = $this->repository->calculateTotalExpenses($this->memberId);

        // Total expenses = 500 + 300 = 800
        $this->assertEquals(800.0, $result);
    }

    public function testCalculateTotalExpensesOnlyIncludesSpecificMember(): void
    {
        $member2Id = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Member 2', 'member2', 80);

        DatabaseSeeder::seedExpense($this->db, $this->memberId, '2025-02-14', 'Groceries', 500.00);
        DatabaseSeeder::seedExpense($this->db, $member2Id, '2025-02-14', 'Other Expense', 1000.00);

        $result = $this->repository->calculateTotalExpenses($this->memberId);

        // Only member1's expenses: 500
        $this->assertEquals(500.0, $result);
    }
}

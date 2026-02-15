<?php

namespace App\Repository;

use App\Dto\ExpenseDto;
use PDO;

class ExpenseRepository extends Repository
{

    /**
     * Find expense record by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM expense WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Create a new expense record.
     *
     * @param int $memberId Member ID who owns this expense
     * @param ExpenseDto $dto Validated expense data
     * @return int The ID of the created expense record
     */
    public function create(int $memberId, ExpenseDto $dto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO expense (memberId, date, reason, amount) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$memberId, $dto->date, $dto->reason, $dto->amount]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update expense record fields (full replacement).
     *
     * @param int $id Expense record ID
     * @param ExpenseDto $dto Validated expense data (all fields required)
     * @return bool True if update was successful
     */
    public function update(int $id, ExpenseDto $dto): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE expense SET amount = ?, reason = ?, date = ? WHERE id = ?'
        );

        return $stmt->execute([$dto->amount, $dto->reason, $dto->date, $id]);
    }

    /**
     * Delete expense record.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM expense WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Calculate total expenses for a member.
     *
     * @param int $memberId
     * @return float Total expenses
     */
    public function calculateTotalExpenses(int $memberId): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM expense WHERE memberId = ?'
        );
        $stmt->execute([$memberId]);
        return (float) $stmt->fetchColumn();
    }
}

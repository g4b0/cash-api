<?php

namespace App\Repository;

use App\Dto\ExpenseCreateDto;
use App\Dto\ExpenseUpdateDto;
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
     * @param int $ownerId Member ID who owns this expense
     * @param ExpenseCreateDto $dto Validated expense data
     * @return int The ID of the created expense record
     */
    public function create(int $ownerId, ExpenseCreateDto $dto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO expense (owner_id, date, reason, amount) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$ownerId, $dto->date, $dto->reason, $dto->amount]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update expense record fields.
     *
     * @param int $id Expense record ID
     * @param ExpenseUpdateDto $dto Validated update data (only non-null fields will be updated)
     * @return bool True if update was successful
     */
    public function update(int $id, ExpenseUpdateDto $dto): bool
    {
        $updates = [];
        $params = [];

        if ($dto->amount !== null) {
            $updates[] = "amount = ?";
            $params[] = $dto->amount;
        }
        if ($dto->reason !== null) {
            $updates[] = "reason = ?";
            $params[] = $dto->reason;
        }
        if ($dto->date !== null) {
            $updates[] = "date = ?";
            $params[] = $dto->date;
        }

        if (empty($updates)) {
            return true;
        }

        $params[] = $id;
        $sql = 'UPDATE expense SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
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
            'SELECT COALESCE(SUM(amount), 0) FROM expense WHERE owner_id = ?'
        );
        $stmt->execute([$memberId]);
        return (float) $stmt->fetchColumn();
    }
}

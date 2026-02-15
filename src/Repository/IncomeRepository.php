<?php

namespace App\Repository;

use App\Dto\IncomeCreateDto;
use App\Dto\IncomeUpdateDto;
use PDO;

class IncomeRepository extends Repository
{

    /**
     * Find income record by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM income WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Create a new income record.
     *
     * @param int $ownerId Member ID who owns this income
     * @param IncomeCreateDto $dto Validated income data
     * @param int $contributionPercentage Contribution percentage (from DTO or member's default)
     * @return int The ID of the created income record
     */
    public function create(int $ownerId, IncomeCreateDto $dto, int $contributionPercentage): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO income (owner_id, date, reason, amount, contribution_percentage) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$ownerId, $dto->date, $dto->reason, $dto->amount, $contributionPercentage]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update income record fields.
     *
     * @param int $id Income record ID
     * @param IncomeUpdateDto $dto Validated update data (only non-null fields will be updated)
     * @return bool True if update was successful
     */
    public function update(int $id, IncomeUpdateDto $dto): bool
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
        if ($dto->contribution_percentage !== null) {
            $updates[] = "contribution_percentage = ?";
            $params[] = $dto->contribution_percentage;
        }

        if (empty($updates)) {
            return true;
        }

        $params[] = $id;
        $sql = 'UPDATE income SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Delete income record.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM income WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Calculate total contributions for a member.
     * Contribution = amount * contribution_percentage / 100
     *
     * @param int $memberId
     * @return float Total contributions
     */
    public function calculateTotalContributions(int $memberId): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount * contribution_percentage / 100.0), 0) FROM income WHERE owner_id = ?'
        );
        $stmt->execute([$memberId]);
        return (float) $stmt->fetchColumn();
    }
}

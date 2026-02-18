<?php

namespace App\Database\Repository;

use App\Http\Dto\IncomeDto;
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
     * @param int $memberId Member ID who owns this income
     * @param IncomeDto $dto Validated income data
     * @param int $contributionPercentage Contribution percentage (from DTO or member's default)
     * @return int The ID of the created income record
     */
    public function create(int $memberId, IncomeDto $dto, int $contributionPercentage): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO income (memberId, date, reason, amount, contributionPercentage) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$memberId, $dto->date, $dto->reason, $dto->amount, $contributionPercentage]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update income record fields (full replacement).
     *
     * @param int $id Income record ID
     * @param IncomeDto $dto Validated income data (all fields required)
     * @param int $contributionPercentage Contribution percentage (from DTO or existing record)
     * @return bool True if update was successful
     */
    public function update(int $id, IncomeDto $dto, int $contributionPercentage): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE income SET amount = ?, reason = ?, date = ?, contributionPercentage = ? WHERE id = ?'
        );

        return $stmt->execute([$dto->amount, $dto->reason, $dto->date, $contributionPercentage, $id]);
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
     * Contribution = amount * contributionPercentage / 100
     *
     * @param int $memberId
     * @return float Total contributions
     */
    public function calculateTotalContributions(int $memberId): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount * contributionPercentage / 100.0), 0) FROM income WHERE memberId = ?'
        );
        $stmt->execute([$memberId]);
        return (float) $stmt->fetchColumn();
    }
}

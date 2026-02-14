<?php

namespace App\Repository;

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
     * @return int The ID of the created income record
     */
    public function create(
        int $ownerId,
        string $date,
        string $reason,
        float $amount,
        int $contributionPercentage
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO income (owner_id, date, reason, amount, contribution_percentage) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$ownerId, $date, $reason, $amount, $contributionPercentage]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update income record fields.
     *
     * @param array $fields Associative array of field => value to update
     * @return bool True if update was successful
     */
    public function update(int $id, array $fields): bool
    {
        if (empty($fields)) {
            return true;
        }

        $updates = [];
        $params = [];

        foreach ($fields as $field => $value) {
            $updates[] = "$field = ?";
            $params[] = $value;
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

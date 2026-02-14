<?php

namespace App\Repository;

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
     * @return int The ID of the created expense record
     */
    public function create(
        int $ownerId,
        string $date,
        string $reason,
        float $amount
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO expense (owner_id, date, reason, amount) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$ownerId, $date, $reason, $amount]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update expense record fields.
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
}

<?php

namespace App\Repository;

use PDO;

class TransactionRepository extends Repository
{

    /**
     * Count total transactions for a member.
     */
    public function countByMemberId(int $memberId): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM (
                SELECT id FROM income WHERE owner_id = ?
                UNION ALL
                SELECT id FROM expense WHERE owner_id = ?
            )
        ');
        $stmt->execute([$memberId, $memberId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Find paginated transactions for a member, sorted by date DESC.
     *
     * @return array Array of transaction records
     */
    public function findPaginatedByMemberId(int $memberId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM (
                SELECT
                    id,
                    "income" as type,
                    date,
                    reason,
                    amount,
                    contribution_percentage,
                    created_at,
                    updated_at
                FROM income WHERE owner_id = ?

                UNION ALL

                SELECT
                    id,
                    "expense" as type,
                    date,
                    reason,
                    amount,
                    NULL as contribution_percentage,
                    created_at,
                    updated_at
                FROM expense WHERE owner_id = ?
            )
            ORDER BY date DESC
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$memberId, $memberId, $limit, $offset]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

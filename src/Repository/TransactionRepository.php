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
                SELECT id FROM income WHERE memberId = ?
                UNION ALL
                SELECT id FROM expense WHERE memberId = ?
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
                    memberId,
                    "income" as type,
                    date,
                    reason,
                    amount,
                    contributionPercentage,
                    createdAt,
                    updatedAt
                FROM income WHERE memberId = ?

                UNION ALL

                SELECT
                    id,
                    memberId,
                    "expense" as type,
                    date,
                    reason,
                    amount,
                    NULL as contributionPercentage,
                    createdAt,
                    updatedAt
                FROM expense WHERE memberId = ?
            )
            ORDER BY date DESC
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$memberId, $memberId, $limit, $offset]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

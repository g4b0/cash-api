<?php

namespace App\Repository;

use PDO;

class MemberRepository extends Repository
{

    /**
     * Find member by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM member WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Find member by username.
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM member WHERE username = ?');
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Find member by ID and verify they belong to the specified community.
     */
    public function findByIdInCommunity(int $memberId, int $communityId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM member WHERE id = ? AND community_id = ?');
        $stmt->execute([$memberId, $communityId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Get member's contribution percentage.
     */
    public function getContributionPercentage(int $memberId): ?int
    {
        $stmt = $this->db->prepare('SELECT contribution_percentage FROM member WHERE id = ?');
        $stmt->execute([$memberId]);
        $result = $stmt->fetchColumn();

        return $result !== false ? (int) $result : null;
    }

    /**
     * Get member's community ID.
     */
    public function getCommunityId(int $memberId): ?int
    {
        $stmt = $this->db->prepare('SELECT community_id FROM member WHERE id = ?');
        $stmt->execute([$memberId]);
        $result = $stmt->fetchColumn();

        return $result !== false ? (int) $result : null;
    }
}

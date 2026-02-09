<?php

namespace App\Service;

use PDO;

class BalanceCalculator
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function calculate(int $memberId): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount * contribution_percentage / 100.0), 0) FROM income WHERE owner_id = ?'
        );
        $stmt->execute([$memberId]);
        $incomeTotal = (float) $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM expense WHERE owner_id = ?'
        );
        $stmt->execute([$memberId]);
        $expenseTotal = (float) $stmt->fetchColumn();

        return $incomeTotal - $expenseTotal;
    }
}

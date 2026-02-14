<?php

namespace App\Service;

use App\Repository\ExpenseRepository;
use App\Repository\IncomeRepository;
use PDO;

class BalanceCalculator
{
    private IncomeRepository $incomeRepository;
    private ExpenseRepository $expenseRepository;

    public function __construct(PDO $db)
    {
        $this->incomeRepository = new IncomeRepository($db);
        $this->expenseRepository = new ExpenseRepository($db);
    }

    /**
     * Calculate balance for a member.
     * Balance = Total Contributions - Total Expenses
     *
     * @param int $memberId
     * @return float
     */
    public function calculate(int $memberId): float
    {
        $incomeTotal = $this->incomeRepository->calculateTotalContributions($memberId);
        $expenseTotal = $this->expenseRepository->calculateTotalExpenses($memberId);

        return $incomeTotal - $expenseTotal;
    }
}

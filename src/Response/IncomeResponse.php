<?php

namespace App\Response;

class IncomeResponse extends MoneyFlowResponse
{
    public int $contributionPercentage;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->contributionPercentage = (int) $data['contribution_percentage'];
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'contribution_percentage' => (string) $this->contributionPercentage,
        ]);
    }
}
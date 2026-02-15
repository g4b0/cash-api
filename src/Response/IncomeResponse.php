<?php

namespace App\Response;

class IncomeResponse extends MoneyFlowResponse
{
    public string $type = 'income';
    public ?string $contributionPercentage; // stored as string for JSON consistency, nullable for flexibility

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->contributionPercentage = $data['contributionPercentage'] !== null
            ? (string) $data['contributionPercentage']
            : null;
    }
}
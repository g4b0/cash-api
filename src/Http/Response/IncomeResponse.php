<?php

namespace App\Http\Response;

class IncomeResponse extends MoneyFlowResponse
{
    public string $type = 'income';
    public ?int $contributionPercentage;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->contributionPercentage = $data['contributionPercentage'] !== null
            ? (int) $data['contributionPercentage']
            : null;
    }
}
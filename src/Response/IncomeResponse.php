<?php

namespace App\Response;

class IncomeResponse extends MoneyFlowResponse
{
    public string $type = 'income';
    public ?string $contribution_percentage; // stored as string for JSON consistency, nullable for flexibility

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->contribution_percentage = $data['contribution_percentage'] !== null
            ? (string) $data['contribution_percentage']
            : null;
    }
}
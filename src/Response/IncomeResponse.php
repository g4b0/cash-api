<?php

namespace App\Response;

class IncomeResponse extends MoneyFlowResponse
{
    public string $contribution_percentage; // stored as string for JSON consistency

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->contribution_percentage = (string) $data['contribution_percentage'];
    }
}
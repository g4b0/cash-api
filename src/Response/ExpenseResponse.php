<?php

namespace App\Response;

class ExpenseResponse extends MoneyFlowResponse
{
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return parent::toArray();
    }
}

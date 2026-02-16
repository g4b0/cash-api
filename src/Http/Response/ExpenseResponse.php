<?php

namespace App\Http\Response;

class ExpenseResponse extends MoneyFlowResponse
{
    public string $type = 'expense';
}

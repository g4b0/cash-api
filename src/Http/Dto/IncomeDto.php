<?php

namespace App\Http\Dto;

use App\Validation\Validator;
use flight\net\Request;

class IncomeDto extends Dto
{
    public float $amount;
    public string $reason;
    public string $date;
    public ?int $contributionPercentage;

    public static function createFromRequest(Request $request): self
    {
        $validator = new Validator();
        $data = $request->data;

        $dto = new self();
        $dto->amount = $validator->validateAmount($data->amount ?? null);
        $dto->reason = $validator->validateReason($data->reason ?? null);
        $dto->date = $validator->validateDate($data->date ?? null);
        $dto->contributionPercentage = $validator->validateContributionPercentage($data->contributionPercentage ?? null);

        return $dto;
    }
}

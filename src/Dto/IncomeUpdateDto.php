<?php

namespace App\Dto;

use App\Validation\Validator;
use flight\net\Request;

class IncomeUpdateDto extends Dto
{
    public ?float $amount = null;
    public ?string $reason = null;
    public ?string $date = null;
    public ?int $contribution_percentage = null;

    public static function createFromRequest(Request $request): self
    {
        $validator = new Validator();
        $data = $request->data;
        $dto = new self();

        if (isset($data->amount)) {
            $dto->amount = $validator->validateAmount($data->amount);
        }
        if (isset($data->reason)) {
            $dto->reason = $validator->validateReason($data->reason);
        }
        if (isset($data->date)) {
            $dto->date = $validator->validateDate($data->date);
        }
        if (isset($data->contribution_percentage)) {
            $dto->contribution_percentage = $validator->validateContributionPercentage($data->contribution_percentage);
        }

        return $dto;
    }
}

<?php

namespace App\Validation;

use App\Exception\AppException;
use DateTime;

class Validator
{
    /**
     * Validate amount is numeric and greater than zero.
     *
     * @param mixed $amount
     * @return float
     * @throws AppException
     */
    public function validateAmount($amount): float
    {
        if (!is_numeric($amount) || (float) $amount <= 0) {
            throw AppException::AMOUNT_MUST_BE_POSITIVE();
        }

        return (float) $amount;
    }

    /**
     * Validate reason is a non-empty string.
     *
     * @param string|null $reason
     * @return string
     * @throws AppException
     */
    public function validateReason(?string $reason): string
    {
        if ($reason === null || trim($reason) === '') {
            throw AppException::REASON_REQUIRED();
        }

        return trim($reason);
    }

    /**
     * Validate date is in YYYY-MM-DD format. Returns today's date if null.
     *
     * @param string|null $date
     * @return string
     * @throws AppException
     */
    public function validateDate(?string $date): string
    {
        if ($date === null) {
            return date('Y-m-d');
        }

        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw AppException::INVALID_DATE_FORMAT();
        }

        return $date;
    }

    /**
     * Validate contribution percentage is between 0 and 100. Returns null if input is null.
     *
     * @param mixed $percentage
     * @return int|null
     * @throws AppException
     */
    public function validateContributionPercentage($percentage): ?int
    {
        if ($percentage === null) {
            return null;
        }

        if (!is_numeric($percentage)) {
            throw AppException::INVALID_CONTRIBUTION_PERCENTAGE();
        }

        $value = (int) $percentage;

        if ($value < 0 || $value > 100) {
            throw AppException::INVALID_CONTRIBUTION_PERCENTAGE();
        }

        return $value;
    }
}

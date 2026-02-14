<?php

namespace App\Exception;

use Exception;

/**
 * Application exception with static factory methods for common errors.
 *
 * @method static self USERNAME_REQUIRED()
 * @method static self PASSWORD_REQUIRED()
 * @method static self INVALID_CREDENTIALS()
 * @method static self REFRESH_TOKEN_REQUIRED()
 * @method static self INVALID_TOKEN_TYPE()
 * @method static self INVALID_OR_EXPIRED_TOKEN()
 * @method static self UNAUTHORIZED()
 * @method static self AMOUNT_MUST_BE_POSITIVE()
 * @method static self REASON_REQUIRED()
 * @method static self INVALID_DATE_FORMAT()
 * @method static self INVALID_CONTRIBUTION_PERCENTAGE()
 * @method static self INCOME_NOT_FOUND()
 * @method static self EXPENSE_NOT_FOUND()
 * @method static self FORBIDDEN()
 * @method static self MEMBER_NOT_FOUND()
 */
class AppException extends Exception
{
    public const USERNAME_REQUIRED = 'Username is required';
    public const PASSWORD_REQUIRED = 'Password is required';
    public const INVALID_CREDENTIALS = 'Invalid credentials';
    public const REFRESH_TOKEN_REQUIRED = 'Refresh token is required';
    public const INVALID_TOKEN_TYPE = 'Invalid token type';
    public const INVALID_OR_EXPIRED_TOKEN = 'Invalid or expired refresh token';
    public const UNAUTHORIZED = 'Unauthorized';
    public const AMOUNT_MUST_BE_POSITIVE = 'Amount must be greater than zero';
    public const REASON_REQUIRED = 'Reason is required';
    public const INVALID_DATE_FORMAT = 'Invalid date format. Use YYYY-MM-DD';
    public const INVALID_CONTRIBUTION_PERCENTAGE = 'Contribution percentage must be between 0 and 100';
    public const INCOME_NOT_FOUND = 'Income record not found';
    public const EXPENSE_NOT_FOUND = 'Expense record not found';
    public const FORBIDDEN = 'You do not have permission to access this resource';
    public const MEMBER_NOT_FOUND = 'Member not found';

    private string $errorKey;

    /**
     * @param array<mixed> $arguments
     */
    public static function __callStatic(string $name, array $arguments): self
    {
        $constantName = "self::$name";
        if (!defined($constantName)) {
            throw new \BadMethodCallException("Undefined exception constant: $name");
        }

        $message = constant($constantName);
        $exception = new self($message);
        $exception->errorKey = $name;

        return $exception;
    }

    public function getStatusCode(): int
    {
        switch ($this->errorKey) {
            case 'INVALID_CREDENTIALS':
            case 'INVALID_TOKEN_TYPE':
            case 'INVALID_OR_EXPIRED_TOKEN':
            case 'UNAUTHORIZED':
                return 401;
            case 'FORBIDDEN':
                return 403;
            case 'INCOME_NOT_FOUND':
            case 'EXPENSE_NOT_FOUND':
            case 'MEMBER_NOT_FOUND':
                return 404;
            default:
                return 400;
        }
    }

    public function getErrorKey(): string
    {
        return $this->errorKey;
    }
}

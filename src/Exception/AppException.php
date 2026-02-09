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
            default:
                return 400;
        }
    }

    public function getErrorKey(): string
    {
        return $this->errorKey;
    }
}

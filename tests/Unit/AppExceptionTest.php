<?php

namespace Tests\Unit;

use App\Exception\AppException;
use PHPUnit\Framework\TestCase;

class AppExceptionTest extends TestCase
{
    public function testUsernameRequiredException(): void
    {
        $exception = AppException::USERNAME_REQUIRED();

        $this->assertInstanceOf(AppException::class, $exception);
        $this->assertEquals('Username is required', $exception->getMessage());
        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertEquals('USERNAME_REQUIRED', $exception->getErrorKey());
    }

    public function testPasswordRequiredException(): void
    {
        $exception = AppException::PASSWORD_REQUIRED();

        $this->assertEquals('Password is required', $exception->getMessage());
        $this->assertEquals(400, $exception->getStatusCode());
    }

    public function testInvalidCredentialsException(): void
    {
        $exception = AppException::INVALID_CREDENTIALS();

        $this->assertEquals('Invalid credentials', $exception->getMessage());
        $this->assertEquals(401, $exception->getStatusCode());
    }

    public function testRefreshTokenRequiredException(): void
    {
        $exception = AppException::REFRESH_TOKEN_REQUIRED();

        $this->assertEquals('Refresh token is required', $exception->getMessage());
        $this->assertEquals(400, $exception->getStatusCode());
    }

    public function testInvalidTokenTypeException(): void
    {
        $exception = AppException::INVALID_TOKEN_TYPE();

        $this->assertEquals('Invalid token type', $exception->getMessage());
        $this->assertEquals(401, $exception->getStatusCode());
    }

    public function testInvalidOrExpiredTokenException(): void
    {
        $exception = AppException::INVALID_OR_EXPIRED_TOKEN();

        $this->assertEquals('Invalid or expired refresh token', $exception->getMessage());
        $this->assertEquals(401, $exception->getStatusCode());
    }

    public function testUnauthorizedException(): void
    {
        $exception = AppException::UNAUTHORIZED();

        $this->assertEquals('Unauthorized', $exception->getMessage());
        $this->assertEquals(401, $exception->getStatusCode());
    }

    public function testUndefinedExceptionThrowsBadMethodCall(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Undefined exception constant: NONEXISTENT');

        AppException::NONEXISTENT();
    }
}

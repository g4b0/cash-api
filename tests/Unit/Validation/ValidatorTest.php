<?php

namespace Tests\Unit\Validation;

use App\Exception\AppException;
use App\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // validateAmount tests
    public function testValidateAmountWithValidPositiveNumber(): void
    {
        $this->assertEquals(100.0, $this->validator->validateAmount(100));
        $this->assertEquals(0.01, $this->validator->validateAmount(0.01));
        $this->assertEquals(1000.50, $this->validator->validateAmount(1000.50));
        $this->assertEquals(42.0, $this->validator->validateAmount('42'));
    }

    public function testValidateAmountRejectsZero(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $this->validator->validateAmount(0);
    }

    public function testValidateAmountRejectsNegative(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $this->validator->validateAmount(-10);
    }

    public function testValidateAmountRejectsNonNumeric(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $this->validator->validateAmount('abc');
    }

    public function testValidateAmountRejectsNull(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $this->validator->validateAmount(null);
    }

    // validateReason tests
    public function testValidateReasonWithValidString(): void
    {
        $this->assertEquals('Salary', $this->validator->validateReason('Salary'));
        $this->assertEquals('Groceries', $this->validator->validateReason('Groceries'));
        $this->assertEquals('Test reason', $this->validator->validateReason('  Test reason  '));
    }

    public function testValidateReasonRejectsEmpty(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Reason is required');

        $this->validator->validateReason('');
    }

    public function testValidateReasonRejectsWhitespace(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Reason is required');

        $this->validator->validateReason('   ');
    }

    public function testValidateReasonRejectsNull(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Reason is required');

        $this->validator->validateReason(null);
    }

    // validateDate tests
    public function testValidateDateWithValidFormat(): void
    {
        $this->assertEquals('2025-02-14', $this->validator->validateDate('2025-02-14'));
        $this->assertEquals('2024-12-31', $this->validator->validateDate('2024-12-31'));
        $this->assertEquals('2025-01-01', $this->validator->validateDate('2025-01-01'));
    }

    public function testValidateDateWithNull(): void
    {
        $result = $this->validator->validateDate(null);
        $this->assertEquals(date('Y-m-d'), $result);
    }

    public function testValidateDateWithInvalidFormat(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid date format. Use YYYY-MM-DD');

        $this->validator->validateDate('14/02/2025');
    }

    public function testValidateDateRejectsInvalidDate(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid date format. Use YYYY-MM-DD');

        $this->validator->validateDate('2025-13-01');
    }

    // validateContributionPercentage tests
    public function testValidateContributionPercentageWithValidNumber(): void
    {
        $this->assertEquals(0, $this->validator->validateContributionPercentage(0));
        $this->assertEquals(50, $this->validator->validateContributionPercentage(50));
        $this->assertEquals(100, $this->validator->validateContributionPercentage(100));
        $this->assertEquals(75, $this->validator->validateContributionPercentage('75'));
    }

    public function testValidateContributionPercentageRejectsNegative(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Contribution percentage must be between 0 and 100');

        $this->validator->validateContributionPercentage(-1);
    }

    public function testValidateContributionPercentageRejectsOverHundred(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Contribution percentage must be between 0 and 100');

        $this->validator->validateContributionPercentage(101);
    }

    public function testValidateContributionPercentageAcceptsNull(): void
    {
        $result = $this->validator->validateContributionPercentage(null);
        $this->assertNull($result);
    }
}

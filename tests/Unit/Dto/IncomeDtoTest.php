<?php

namespace Tests\Unit\Dto;

use App\Dto\IncomeDto;
use App\Exception\AppException;
use flight\net\Request;
use PHPUnit\Framework\TestCase;

class IncomeDtoTest extends TestCase
{
    private function createRequest(array $data): Request
    {
        $request = new Request();
        $request->data->setData($data);
        return $request;
    }

    public function testCreateFromRequestWithValidData(): void
    {
        $request = $this->createRequest([
            'amount' => 1500.50,
            'reason' => 'Monthly salary',
            'date' => '2025-02-15',
            'contribution_percentage' => 75
        ]);

        $dto = IncomeDto::createFromRequest($request);

        $this->assertSame(1500.50, $dto->amount);
        $this->assertSame('Monthly salary', $dto->reason);
        $this->assertSame('2025-02-15', $dto->date);
        $this->assertSame(75, $dto->contribution_percentage);
    }

    public function testCreateFromRequestWithMinimalData(): void
    {
        $request = $this->createRequest([
            'amount' => 100,
            'reason' => 'Bonus'
        ]);

        $dto = IncomeDto::createFromRequest($request);

        $this->assertSame(100.0, $dto->amount);
        $this->assertSame('Bonus', $dto->reason);
        $this->assertSame(date('Y-m-d'), $dto->date);  // Defaults to today
        $this->assertNull($dto->contribution_percentage);  // Optional
    }

    public function testCreateFromRequestWithMissingAmountThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $request = $this->createRequest([
            'reason' => 'Test'
        ]);

        IncomeDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithZeroAmountThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $request = $this->createRequest([
            'amount' => 0,
            'reason' => 'Test'
        ]);

        IncomeDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithNegativeAmountThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $request = $this->createRequest([
            'amount' => -100,
            'reason' => 'Test'
        ]);

        IncomeDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithMissingReasonThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Reason is required');

        $request = $this->createRequest([
            'amount' => 100
        ]);

        IncomeDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithEmptyReasonThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Reason is required');

        $request = $this->createRequest([
            'amount' => 100,
            'reason' => '   '
        ]);

        IncomeDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithInvalidDateFormatThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid date format');

        $request = $this->createRequest([
            'amount' => 100,
            'reason' => 'Test',
            'date' => '15/02/2025'
        ]);

        IncomeDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithInvalidContributionPercentageThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Contribution percentage must be between 0 and 100');

        $request = $this->createRequest([
            'amount' => 100,
            'reason' => 'Test',
            'contribution_percentage' => 101
        ]);

        IncomeDto::createFromRequest($request);
    }

    public function testCreateFromRequestTrimsReasonWhitespace(): void
    {
        $request = $this->createRequest([
            'amount' => 100,
            'reason' => '  Salary  '
        ]);

        $dto = IncomeDto::createFromRequest($request);

        $this->assertSame('Salary', $dto->reason);
    }

    public function testCreateFromRequestCastsAmountToFloat(): void
    {
        $request = $this->createRequest([
            'amount' => '1500',
            'reason' => 'Test'
        ]);

        $dto = IncomeDto::createFromRequest($request);

        $this->assertSame(1500.0, $dto->amount);
        $this->assertIsFloat($dto->amount);
    }

    public function testCreateFromRequestCastsContributionPercentageToInt(): void
    {
        $request = $this->createRequest([
            'amount' => 100,
            'reason' => 'Test',
            'contribution_percentage' => '85'
        ]);

        $dto = IncomeDto::createFromRequest($request);

        $this->assertSame(85, $dto->contribution_percentage);
        $this->assertIsInt($dto->contribution_percentage);
    }
}

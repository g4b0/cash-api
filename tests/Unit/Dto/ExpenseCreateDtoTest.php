<?php

namespace Tests\Unit\Dto;

use App\Dto\ExpenseCreateDto;
use App\Exception\AppException;
use flight\net\Request;
use PHPUnit\Framework\TestCase;

class ExpenseCreateDtoTest extends TestCase
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
            'amount' => 500.75,
            'reason' => 'Groceries',
            'date' => '2025-02-15'
        ]);

        $dto = ExpenseCreateDto::createFromRequest($request);

        $this->assertSame(500.75, $dto->amount);
        $this->assertSame('Groceries', $dto->reason);
        $this->assertSame('2025-02-15', $dto->date);
    }

    public function testCreateFromRequestWithMinimalData(): void
    {
        $request = $this->createRequest([
            'amount' => 100,
            'reason' => 'Utilities'
        ]);

        $dto = ExpenseCreateDto::createFromRequest($request);

        $this->assertSame(100.0, $dto->amount);
        $this->assertSame('Utilities', $dto->reason);
        $this->assertSame(date('Y-m-d'), $dto->date);  // Defaults to today
    }

    public function testCreateFromRequestWithMissingAmountThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $request = $this->createRequest([
            'reason' => 'Test'
        ]);

        ExpenseCreateDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithZeroAmountThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $request = $this->createRequest([
            'amount' => 0,
            'reason' => 'Test'
        ]);

        ExpenseCreateDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithNegativeAmountThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $request = $this->createRequest([
            'amount' => -100,
            'reason' => 'Test'
        ]);

        ExpenseCreateDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithMissingReasonThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Reason is required');

        $request = $this->createRequest([
            'amount' => 100
        ]);

        ExpenseCreateDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithEmptyReasonThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Reason is required');

        $request = $this->createRequest([
            'amount' => 100,
            'reason' => '   '
        ]);

        ExpenseCreateDto::createFromRequest($request);
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

        ExpenseCreateDto::createFromRequest($request);
    }

    public function testCreateFromRequestTrimsReasonWhitespace(): void
    {
        $request = $this->createRequest([
            'amount' => 100,
            'reason' => '  Groceries  '
        ]);

        $dto = ExpenseCreateDto::createFromRequest($request);

        $this->assertSame('Groceries', $dto->reason);
    }

    public function testCreateFromRequestCastsAmountToFloat(): void
    {
        $request = $this->createRequest([
            'amount' => '500',
            'reason' => 'Test'
        ]);

        $dto = ExpenseCreateDto::createFromRequest($request);

        $this->assertSame(500.0, $dto->amount);
        $this->assertIsFloat($dto->amount);
    }
}

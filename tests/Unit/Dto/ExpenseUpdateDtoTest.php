<?php

namespace Tests\Unit\Dto;

use App\Dto\ExpenseUpdateDto;
use App\Exception\AppException;
use flight\net\Request;
use PHPUnit\Framework\TestCase;

class ExpenseUpdateDtoTest extends TestCase
{
    private function createRequest(array $data): Request
    {
        $request = new Request();
        $request->data->setData($data);
        return $request;
    }

    public function testCreateFromRequestWithAllFields(): void
    {
        $request = $this->createRequest([
            'amount' => 600.50,
            'reason' => 'Updated Groceries',
            'date' => '2025-02-15'
        ]);

        $dto = ExpenseUpdateDto::createFromRequest($request);

        $this->assertSame(600.50, $dto->amount);
        $this->assertSame('Updated Groceries', $dto->reason);
        $this->assertSame('2025-02-15', $dto->date);
    }

    public function testCreateFromRequestWithAmountOnly(): void
    {
        $request = $this->createRequest([
            'amount' => 700
        ]);

        $dto = ExpenseUpdateDto::createFromRequest($request);

        $this->assertSame(700.0, $dto->amount);
        $this->assertNull($dto->reason);
        $this->assertNull($dto->date);
    }

    public function testCreateFromRequestWithReasonOnly(): void
    {
        $request = $this->createRequest([
            'reason' => 'Utilities'
        ]);

        $dto = ExpenseUpdateDto::createFromRequest($request);

        $this->assertSame('Utilities', $dto->reason);
        $this->assertNull($dto->amount);
        $this->assertNull($dto->date);
    }

    public function testCreateFromRequestWithDateOnly(): void
    {
        $request = $this->createRequest([
            'date' => '2025-03-01'
        ]);

        $dto = ExpenseUpdateDto::createFromRequest($request);

        $this->assertSame('2025-03-01', $dto->date);
        $this->assertNull($dto->amount);
        $this->assertNull($dto->reason);
    }

    public function testCreateFromRequestWithNoFieldsReturnsAllNull(): void
    {
        $request = $this->createRequest([]);

        $dto = ExpenseUpdateDto::createFromRequest($request);

        $this->assertNull($dto->amount);
        $this->assertNull($dto->reason);
        $this->assertNull($dto->date);
    }

    public function testCreateFromRequestWithInvalidAmountThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $request = $this->createRequest([
            'amount' => 0
        ]);

        ExpenseUpdateDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithNegativeAmountThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $request = $this->createRequest([
            'amount' => -100
        ]);

        ExpenseUpdateDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithInvalidReasonThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Reason is required');

        $request = $this->createRequest([
            'reason' => '   '
        ]);

        ExpenseUpdateDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithInvalidDateThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid date format');

        $request = $this->createRequest([
            'date' => 'invalid'
        ]);

        ExpenseUpdateDto::createFromRequest($request);
    }

    public function testCreateFromRequestTrimsReasonWhitespace(): void
    {
        $request = $this->createRequest([
            'reason' => '  Updated  '
        ]);

        $dto = ExpenseUpdateDto::createFromRequest($request);

        $this->assertSame('Updated', $dto->reason);
    }

    public function testCreateFromRequestCastsAmountToFloat(): void
    {
        $request = $this->createRequest([
            'amount' => '600'
        ]);

        $dto = ExpenseUpdateDto::createFromRequest($request);

        $this->assertSame(600.0, $dto->amount);
        $this->assertIsFloat($dto->amount);
    }
}

<?php

namespace Tests\Unit\Dto;

use App\Dto\IncomeUpdateDto;
use App\Exception\AppException;
use flight\net\Request;
use PHPUnit\Framework\TestCase;

class IncomeUpdateDtoTest extends TestCase
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
            'amount' => 1500.50,
            'reason' => 'Updated Salary',
            'date' => '2025-02-15',
            'contribution_percentage' => 85
        ]);

        $dto = IncomeUpdateDto::createFromRequest($request);

        $this->assertSame(1500.50, $dto->amount);
        $this->assertSame('Updated Salary', $dto->reason);
        $this->assertSame('2025-02-15', $dto->date);
        $this->assertSame(85, $dto->contribution_percentage);
    }

    public function testCreateFromRequestWithAmountOnly(): void
    {
        $request = $this->createRequest([
            'amount' => 2000
        ]);

        $dto = IncomeUpdateDto::createFromRequest($request);

        $this->assertSame(2000.0, $dto->amount);
        $this->assertNull($dto->reason);
        $this->assertNull($dto->date);
        $this->assertNull($dto->contribution_percentage);
    }

    public function testCreateFromRequestWithReasonOnly(): void
    {
        $request = $this->createRequest([
            'reason' => 'Bonus'
        ]);

        $dto = IncomeUpdateDto::createFromRequest($request);

        $this->assertSame('Bonus', $dto->reason);
        $this->assertNull($dto->amount);
        $this->assertNull($dto->date);
        $this->assertNull($dto->contribution_percentage);
    }

    public function testCreateFromRequestWithDateOnly(): void
    {
        $request = $this->createRequest([
            'date' => '2025-03-01'
        ]);

        $dto = IncomeUpdateDto::createFromRequest($request);

        $this->assertSame('2025-03-01', $dto->date);
        $this->assertNull($dto->amount);
        $this->assertNull($dto->reason);
        $this->assertNull($dto->contribution_percentage);
    }

    public function testCreateFromRequestWithContributionPercentageOnly(): void
    {
        $request = $this->createRequest([
            'contribution_percentage' => 90
        ]);

        $dto = IncomeUpdateDto::createFromRequest($request);

        $this->assertSame(90, $dto->contribution_percentage);
        $this->assertNull($dto->amount);
        $this->assertNull($dto->reason);
        $this->assertNull($dto->date);
    }

    public function testCreateFromRequestWithNoFieldsReturnsAllNull(): void
    {
        $request = $this->createRequest([]);

        $dto = IncomeUpdateDto::createFromRequest($request);

        $this->assertNull($dto->amount);
        $this->assertNull($dto->reason);
        $this->assertNull($dto->date);
        $this->assertNull($dto->contribution_percentage);
    }

    public function testCreateFromRequestWithInvalidAmountThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $request = $this->createRequest([
            'amount' => 0
        ]);

        IncomeUpdateDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithInvalidReasonThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Reason is required');

        $request = $this->createRequest([
            'reason' => '   '
        ]);

        IncomeUpdateDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithInvalidDateThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid date format');

        $request = $this->createRequest([
            'date' => 'invalid'
        ]);

        IncomeUpdateDto::createFromRequest($request);
    }

    public function testCreateFromRequestWithInvalidContributionPercentageThrowsException(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Contribution percentage must be between 0 and 100');

        $request = $this->createRequest([
            'contribution_percentage' => 101
        ]);

        IncomeUpdateDto::createFromRequest($request);
    }

    public function testCreateFromRequestTrimsReasonWhitespace(): void
    {
        $request = $this->createRequest([
            'reason' => '  Updated  '
        ]);

        $dto = IncomeUpdateDto::createFromRequest($request);

        $this->assertSame('Updated', $dto->reason);
    }

    public function testCreateFromRequestCastsTypes(): void
    {
        $request = $this->createRequest([
            'amount' => '1500',
            'contribution_percentage' => '85'
        ]);

        $dto = IncomeUpdateDto::createFromRequest($request);

        $this->assertSame(1500.0, $dto->amount);
        $this->assertIsFloat($dto->amount);
        $this->assertSame(85, $dto->contribution_percentage);
        $this->assertIsInt($dto->contribution_percentage);
    }
}

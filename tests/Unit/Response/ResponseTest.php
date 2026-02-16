<?php

namespace Tests\Unit\Response;

use App\Http\Response\BalanceResponse;
use App\Http\Response\Crud\CreatedResourceResponse;
use App\Http\Response\Crud\NoContentResponse;
use App\Http\Response\TokenPairResponse;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testCreatedResourceResponseReturns201AndLocationHeader(): void
    {
        $response = new CreatedResourceResponse(123, 'income');

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(['id' => 123], $response->toArray());
        $this->assertEquals('/income/123', $response->getLocationHeader());
    }

    public function testCreatedResourceResponseJsonSerializable(): void
    {
        $response = new CreatedResourceResponse(456, 'expense');

        $json = json_encode($response);
        $this->assertEquals('{"id":456}', $json);
    }

    public function testNoContentResponseReturns204WithEmptyBody(): void
    {
        $response = new NoContentResponse();

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([], $response->toArray());
        $this->assertNull($response->getLocationHeader());
    }

    public function testNoContentResponseJsonSerializable(): void
    {
        $response = new NoContentResponse();

        $json = json_encode($response);
        $this->assertEquals('[]', $json);
    }

    public function testTokenPairResponseReturnsTokens(): void
    {
        $accessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.access';
        $refreshToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.refresh';

        $response = new TokenPairResponse($accessToken, $refreshToken);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ], $response->toArray());
        $this->assertNull($response->getLocationHeader());
    }

    public function testBalanceResponseReturnsBalance(): void
    {
        $response = new BalanceResponse(1, 1250.50);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'memberId' => 1,
            'balance' => 1250.50,
        ], $response->toArray());
        $this->assertNull($response->getLocationHeader());
    }

    public function testBalanceResponseRoundsToTwoDecimals(): void
    {
        // Float with many decimals - rounded to 2 decimal places
        $response = new BalanceResponse(2, 625.123456789);

        $array = $response->toArray();
        $this->assertIsFloat($array['balance']);
        $this->assertEquals(625.12, $array['balance']); // Rounded to 2 decimals
    }

    public function testJsonSerializableInterfaceWorks(): void
    {
        // Test that all response classes implement JsonSerializable correctly
        $responses = [
            new CreatedResourceResponse(1, 'test'),
            new NoContentResponse(),
            new TokenPairResponse('access', 'refresh'),
            new BalanceResponse(1, 123.45),
        ];

        foreach ($responses as $response) {
            $this->assertInstanceOf(\JsonSerializable::class, $response);

            // Verify json_encode works
            $json = json_encode($response);
            $this->assertIsString($json);
            $this->assertNotFalse($json);

            // Verify decoding matches toArray()
            $decoded = json_decode($json, true);
            $this->assertEquals($response->toArray(), $decoded);
        }
    }
}

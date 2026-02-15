<?php

namespace Tests\Unit\Response;

use App\Response\CreatedResourceResponse;
use App\Response\MetricResponse;
use App\Response\NoContentResponse;
use App\Response\TokenPairResponse;
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
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ], $response->toArray());
        $this->assertNull($response->getLocationHeader());
    }

    public function testMetricResponseReturnsNamedMetric(): void
    {
        $response = new MetricResponse('balance', 1250.50);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['balance' => 1250.50], $response->toArray());
        $this->assertNull($response->getLocationHeader());
    }

    public function testMetricResponseSupportsIntegerValues(): void
    {
        $response = new MetricResponse('count', 42);

        $this->assertEquals(['count' => 42], $response->toArray());
    }

    public function testJsonSerializableInterfaceWorks(): void
    {
        // Test that all response classes implement JsonSerializable correctly
        $responses = [
            new CreatedResourceResponse(1, 'test'),
            new NoContentResponse(),
            new TokenPairResponse('access', 'refresh'),
            new MetricResponse('metric', 123),
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

<?php

namespace Tests\Feature;

use App\Service\JwtService;
use PDO;
use PHPUnit\Framework\TestCase;
use flight\Engine;
use Tests\Support\DatabaseSeeder;

class RefreshTokenRouteTest extends TestCase
{
    private Engine $app;
    private PDO $db;
    private int $memberId;
    private int $communityId;

    protected function setUp(): void
    {
        putenv('PHPUNIT_TEST=1');

        $this->db = DatabaseSeeder::createDatabase();
        $this->app = new Engine();
        $this->app->set('db', $this->db);
        $this->app->set('jwt_secret', 'test-secret');

        $this->communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        $this->memberId = DatabaseSeeder::seedMember($this->db, $this->communityId, 'Test User', 'testuser', 75);

        registerRoutes($this->app);
    }

    public function testRefreshWithValidToken(): void
    {
        $jwtService = new JwtService('test-secret');
        $refreshToken = $jwtService->generateRefreshToken($this->memberId, $this->communityId);

        $this->app->request()->url = '/refresh';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData(['refreshToken' => $refreshToken]);

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('accessToken', $body);
        $this->assertArrayHasKey('refreshToken', $body);
        $this->assertNotEmpty($body['accessToken']);
        $this->assertNotEmpty($body['refreshToken']);
    }

    public function testRefreshWithInvalidToken(): void
    {
        $this->app->request()->url = '/refresh';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData(['refreshToken' => 'garbage-token']);

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testRefreshWithAccessTokenInsteadOfRefresh(): void
    {
        $jwtService = new JwtService('test-secret');
        $accessToken = $jwtService->generateAccessToken($this->memberId, $this->communityId);

        $this->app->request()->url = '/refresh';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData(['refreshToken' => $accessToken]);

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }
}

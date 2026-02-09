<?php

namespace Tests\Feature;

use PDO;
use PHPUnit\Framework\TestCase;
use flight\Engine;
use Tests\Support\DatabaseSeeder;

class LoginRouteTest extends TestCase
{
    private Engine $app;
    private PDO $db;

    protected function setUp(): void
    {
        putenv('PHPUNIT_TEST=1');

        $this->db = DatabaseSeeder::createDatabase();
        $this->app = new Engine();
        $this->app->set('db', $this->db);
        $this->app->set('jwt_secret', 'test-secret');

        registerRoutes($this->app);
    }

    public function testLoginMissingUsername(): void
    {
        $this->app->request()->url = '/login';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData(['password' => 'secret']);

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testLoginMissingPassword(): void
    {
        $this->app->request()->url = '/login';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData(['username' => 'test']);

        $this->app->start();

        $this->assertEquals(400, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testLoginInvalidCredentials(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        DatabaseSeeder::seedMember($this->db, $communityId, 'Test User', 'testuser', 75);

        $this->app->request()->url = '/login';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData(['username' => 'testuser', 'password' => 'wrong']);

        $this->app->start();

        $this->assertEquals(401, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testLoginSuccess(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Famiglia');
        DatabaseSeeder::seedMember($this->db, $communityId, 'Test User', 'testuser', 75);

        $this->app->request()->url = '/login';
        $this->app->request()->method = 'POST';
        $this->app->request()->data->setData(['username' => 'testuser', 'password' => 'test']);

        $this->app->start();

        $this->assertEquals(200, $this->app->response()->status());

        $body = json_decode($this->app->response()->getBody(), true);
        $this->assertArrayHasKey('access_token', $body);
        $this->assertArrayHasKey('refresh_token', $body);
        $this->assertNotEmpty($body['access_token']);
        $this->assertNotEmpty($body['refresh_token']);
    }
}

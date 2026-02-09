<?php

namespace Tests\Unit\Command;

use App\Command\CommunityCommand;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseSeeder;

class CommunityCommandTest extends TestCase
{
    private PDO $db;
    private $stdout;
    private $stderr;

    protected function setUp(): void
    {
        $this->db = DatabaseSeeder::createDatabase();
        $this->stdout = fopen('php://memory', 'w+');
        $this->stderr = fopen('php://memory', 'w+');
    }

    protected function tearDown(): void
    {
        fclose($this->stdout);
        fclose($this->stderr);
    }

    private function getOutput(): string
    {
        rewind($this->stdout);
        return stream_get_contents($this->stdout);
    }

    private function getErrorOutput(): string
    {
        rewind($this->stderr);
        return stream_get_contents($this->stderr);
    }

    public function testAddCommunity(): void
    {
        $command = new CommunityCommand($this->db, [
            'action' => 'add',
            'name' => 'Test Family',
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getOutput();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Community created with ID:', $output);

        // Verify in database
        $stmt = $this->db->query('SELECT * FROM community WHERE name = "Test Family"');
        $community = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($community);
        $this->assertEquals('Test Family', $community['name']);
    }

    public function testAddCommunityWithoutName(): void
    {
        $command = new CommunityCommand($this->db, [
            'action' => 'add',
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getErrorOutput();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Community name is required', $output);
    }

    public function testUpdateCommunity(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Original Name');

        $command = new CommunityCommand($this->db, [
            'action' => 'update',
            'id' => $communityId,
            'name' => 'Updated Name',
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getOutput();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString("Community $communityId updated successfully", $output);

        // Verify in database
        $stmt = $this->db->prepare('SELECT name FROM community WHERE id = ?');
        $stmt->execute([$communityId]);
        $name = $stmt->fetchColumn();

        $this->assertEquals('Updated Name', $name);
    }

    public function testUpdateNonExistentCommunity(): void
    {
        $command = new CommunityCommand($this->db, [
            'action' => 'update',
            'id' => 9999,
            'name' => 'New Name',
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getErrorOutput();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Community with ID 9999 not found', $output);
    }

    public function testUpdateWithoutId(): void
    {
        $command = new CommunityCommand($this->db, [
            'action' => 'update',
            'name' => 'New Name',
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getErrorOutput();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Community ID is required', $output);
    }

    public function testDeleteCommunity(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'To Delete');

        $command = new CommunityCommand($this->db, [
            'action' => 'delete',
            'id' => $communityId,
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getOutput();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString("Community $communityId deleted successfully", $output);

        // Verify deleted from database
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM community WHERE id = ?');
        $stmt->execute([$communityId]);
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count);
    }

    public function testDeleteNonExistentCommunity(): void
    {
        $command = new CommunityCommand($this->db, [
            'action' => 'delete',
            'id' => 9999,
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getErrorOutput();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Community with ID 9999 not found', $output);
    }

    public function testInvalidAction(): void
    {
        $command = new CommunityCommand($this->db, [
            'action' => 'invalid',
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getErrorOutput();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Invalid action', $output);
    }
}

<?php

namespace Tests\Unit\Command;

use App\Command\MemberCommand;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseSeeder;

class MemberCommandTest extends TestCase
{
    private PDO $db;
    private int $communityId;
    private $stdout;
    private $stderr;

    protected function setUp(): void
    {
        $this->db = DatabaseSeeder::createDatabase();
        $this->communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
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

    public function testAddMember(): void
    {
        $command = new MemberCommand($this->db, [
            'action' => 'add',
            'community_id' => $this->communityId,
            'name' => 'John Doe',
            'username' => 'johndoe',
            'password' => 'secret123',
            'contribution_percentage' => 80,
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getOutput();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Member created with ID:', $output);

        $stmt = $this->db->query('SELECT * FROM member WHERE username = "johndoe"');
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($member);
        $this->assertEquals('John Doe', $member['name']);
        $this->assertEquals('johndoe', $member['username']);
        $this->assertEquals(80, $member['contribution_percentage']);
        $this->assertTrue(password_verify('secret123', $member['password']));
    }

    public function testAddMemberWithDefaultContribution(): void
    {
        $command = new MemberCommand($this->db, [
            'action' => 'add',
            'community_id' => $this->communityId,
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'password' => 'secret456',
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();

        $this->assertEquals(0, $exitCode);

        $stmt = $this->db->query('SELECT contribution_percentage FROM member WHERE username = "janedoe"');
        $contributionPercentage = $stmt->fetchColumn();

        $this->assertEquals(75, $contributionPercentage);
    }

    public function testAddMemberWithoutCommunityId(): void
    {
        $command = new MemberCommand($this->db, [
            'action' => 'add',
            'name' => 'John Doe',
            'username' => 'johndoe',
            'password' => 'secret',
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getErrorOutput();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Community ID is required', $output);
    }

    public function testDeleteMember(): void
    {
        $memberId = DatabaseSeeder::seedMember($this->db, $this->communityId, 'To Delete', 'todelete', 75);

        $command = new MemberCommand($this->db, [
            'action' => 'delete',
            'id' => $memberId,
        ], $this->stdout, $this->stderr);

        $exitCode = $command->execute();
        $output = $this->getOutput();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString("Member $memberId deleted successfully", $output);

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM member WHERE id = ?');
        $stmt->execute([$memberId]);
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count);
    }
}

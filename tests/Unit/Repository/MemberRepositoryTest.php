<?php

namespace Tests\Unit\Repository;

use App\Repository\MemberRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseSeeder;

class MemberRepositoryTest extends TestCase
{
    private PDO $db;
    private MemberRepository $repository;

    protected function setUp(): void
    {
        $this->db = DatabaseSeeder::createDatabase();
        $this->repository = new MemberRepository($this->db);
    }

    public function testFindByIdReturnsArrayWhenMemberExists(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'John Doe', 'johndoe', 75);

        $result = $this->repository->findById($memberId);

        $this->assertIsArray($result);
        $this->assertEquals($memberId, $result['id']);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('johndoe', $result['username']);
        $this->assertEquals(75, $result['contribution_percentage']);
    }

    public function testFindByIdReturnsNullWhenMemberDoesNotExist(): void
    {
        $result = $this->repository->findById(99999);

        $this->assertNull($result);
    }

    public function testFindByUsernameReturnsArrayWhenMemberExists(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
        DatabaseSeeder::seedMember($this->db, $communityId, 'Jane Doe', 'janedoe', 80);

        $result = $this->repository->findByUsername('janedoe');

        $this->assertIsArray($result);
        $this->assertEquals('Jane Doe', $result['name']);
        $this->assertEquals('janedoe', $result['username']);
    }

    public function testFindByUsernameReturnsNullWhenMemberDoesNotExist(): void
    {
        $result = $this->repository->findByUsername('nonexistent');

        $this->assertNull($result);
    }

    public function testFindByIdInCommunityReturnsArrayWhenMemberBelongsToCommunity(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'John Doe', 'johndoe', 75);

        $result = $this->repository->findByIdInCommunity($memberId, $communityId);

        $this->assertIsArray($result);
        $this->assertEquals($memberId, $result['id']);
        $this->assertEquals($communityId, $result['community_id']);
    }

    public function testFindByIdInCommunityReturnsNullWhenMemberBelongsToDifferentCommunity(): void
    {
        $community1Id = DatabaseSeeder::seedCommunity($this->db, 'Community 1');
        $community2Id = DatabaseSeeder::seedCommunity($this->db, 'Community 2');
        $memberId = DatabaseSeeder::seedMember($this->db, $community1Id, 'John Doe', 'johndoe', 75);

        $result = $this->repository->findByIdInCommunity($memberId, $community2Id);

        $this->assertNull($result);
    }

    public function testGetContributionPercentageReturnsIntWhenMemberExists(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'John Doe', 'johndoe', 85);

        $result = $this->repository->getContributionPercentage($memberId);

        $this->assertEquals(85, $result);
    }

    public function testGetContributionPercentageReturnsNullWhenMemberDoesNotExist(): void
    {
        $result = $this->repository->getContributionPercentage(99999);

        $this->assertNull($result);
    }

    public function testGetCommunityIdReturnsIntWhenMemberExists(): void
    {
        $communityId = DatabaseSeeder::seedCommunity($this->db, 'Test Community');
        $memberId = DatabaseSeeder::seedMember($this->db, $communityId, 'John Doe', 'johndoe', 75);

        $result = $this->repository->getCommunityId($memberId);

        $this->assertEquals($communityId, $result);
    }

    public function testGetCommunityIdReturnsNullWhenMemberDoesNotExist(): void
    {
        $result = $this->repository->getCommunityId(99999);

        $this->assertNull($result);
    }
}

<?php

namespace Tests\Support;

use PDO;

class DatabaseSeeder
{
    public static function createDatabase(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $schema = file_get_contents(__DIR__ . '/../../database/schema.sql');
        $pdo->exec($schema);

        return $pdo;
    }

    public static function seedCommunity(PDO $db, string $name): int
    {
        $stmt = $db->prepare('INSERT INTO community (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);

        return (int) $db->lastInsertId();
    }

    public static function seedMember(
        PDO $db,
        int $communityId,
        string $name,
        string $username,
        int $contributionPercentage = 75
    ): int {
        $stmt = $db->prepare(
            'INSERT INTO member (community_id, name, username, password, contribution_percentage)
             VALUES (:community_id, :name, :username, :password, :contribution_percentage)'
        );
        $stmt->execute([
            'community_id' => $communityId,
            'name' => $name,
            'username' => $username,
            'password' => password_hash('test', PASSWORD_DEFAULT),
            'contribution_percentage' => $contributionPercentage,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function seedIncome(
        PDO $db,
        int $ownerId,
        string $date,
        string $reason,
        float $amount,
        int $contributionPercentage
    ): int {
        $stmt = $db->prepare(
            'INSERT INTO income (owner_id, date, reason, amount, contribution_percentage)
             VALUES (:owner_id, :date, :reason, :amount, :contribution_percentage)'
        );
        $stmt->execute([
            'owner_id' => $ownerId,
            'date' => $date,
            'reason' => $reason,
            'amount' => $amount,
            'contribution_percentage' => $contributionPercentage,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function seedExpense(
        PDO $db,
        int $ownerId,
        string $date,
        string $reason,
        float $amount
    ): int {
        $stmt = $db->prepare(
            'INSERT INTO expense (owner_id, date, reason, amount)
             VALUES (:owner_id, :date, :reason, :amount)'
        );
        $stmt->execute([
            'owner_id' => $ownerId,
            'date' => $date,
            'reason' => $reason,
            'amount' => $amount,
        ]);

        return (int) $db->lastInsertId();
    }
}

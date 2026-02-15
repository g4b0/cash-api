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
            'INSERT INTO member (communityId, name, username, password, contributionPercentage)
             VALUES (:communityId, :name, :username, :password, :contributionPercentage)'
        );
        $stmt->execute([
            'communityId' => $communityId,
            'name' => $name,
            'username' => $username,
            'password' => password_hash('test', PASSWORD_DEFAULT),
            'contributionPercentage' => $contributionPercentage,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function seedIncome(
        PDO $db,
        int $memberId,
        string $date,
        string $reason,
        float $amount,
        int $contributionPercentage
    ): int {
        $stmt = $db->prepare(
            'INSERT INTO income (memberId, date, reason, amount, contributionPercentage)
             VALUES (:memberId, :date, :reason, :amount, :contributionPercentage)'
        );
        $stmt->execute([
            'memberId' => $memberId,
            'date' => $date,
            'reason' => $reason,
            'amount' => $amount,
            'contributionPercentage' => $contributionPercentage,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function seedExpense(
        PDO $db,
        int $memberId,
        string $date,
        string $reason,
        float $amount
    ): int {
        $stmt = $db->prepare(
            'INSERT INTO expense (memberId, date, reason, amount)
             VALUES (:memberId, :date, :reason, :amount)'
        );
        $stmt->execute([
            'memberId' => $memberId,
            'date' => $date,
            'reason' => $reason,
            'amount' => $amount,
        ]);

        return (int) $db->lastInsertId();
    }
}

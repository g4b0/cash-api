<?php

namespace App\Command;

class MemberCommand extends AbstractCommand
{
    public function execute(): int
    {
        $action = $this->args['action'] ?? null;

        switch ($action) {
            case 'add':
                return $this->add();
            case 'update':
                return $this->update();
            case 'delete':
                return $this->delete();
            default:
                $this->error("Invalid action: $action. Use 'add', 'update', or 'delete'.");
                return 1;
        }
    }

    private function add(): int
    {
        $communityId = $this->args['community_id'] ?? null;
        $name = $this->args['name'] ?? null;
        $username = $this->args['username'] ?? null;
        $password = $this->args['password'] ?? null;
        $contributionPercentage = $this->args['contribution_percentage'] ?? 75;

        if (empty($communityId)) {
            $this->error("Community ID is required. Use --community_id=123");
            return 1;
        }

        if (empty($name)) {
            $this->error("Member name is required. Use --name=\"John Doe\"");
            return 1;
        }

        if (empty($username)) {
            $this->error("Username is required. Use --username=johndoe");
            return 1;
        }

        if (empty($password)) {
            $this->error("Password is required. Use --password=secret");
            return 1;
        }

        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare(
                'INSERT INTO member (community_id, name, username, password, contribution_percentage) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$communityId, $name, $username, $hashedPassword, $contributionPercentage]);

            $id = (int) $this->db->lastInsertId();
            $this->success("Member created with ID: $id");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create member: " . $e->getMessage());
            return 1;
        }
    }

    private function update(): int
    {
        $id = $this->args['id'] ?? null;
        $name = $this->args['name'] ?? null;
        $username = $this->args['username'] ?? null;
        $contributionPercentage = $this->args['contribution_percentage'] ?? null;

        if (empty($id)) {
            $this->error("Member ID is required. Use --id=123");
            return 1;
        }

        $updates = [];
        $values = [];

        if (!empty($name)) {
            $updates[] = 'name = ?';
            $values[] = $name;
        }

        if (!empty($username)) {
            $updates[] = 'username = ?';
            $values[] = $username;
        }

        if ($contributionPercentage !== null) {
            $updates[] = 'contribution_percentage = ?';
            $values[] = $contributionPercentage;
        }

        if (empty($updates)) {
            $this->error("At least one field to update is required (--name, --username, --contribution_percentage)");
            return 1;
        }

        try {
            $values[] = $id;
            $sql = 'UPDATE member SET ' . implode(', ', $updates) . ' WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            if ($stmt->rowCount() === 0) {
                $this->error("Member with ID $id not found");
                return 1;
            }

            $this->success("Member $id updated successfully");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to update member: " . $e->getMessage());
            return 1;
        }
    }

    private function delete(): int
    {
        $id = $this->args['id'] ?? null;

        if (empty($id)) {
            $this->error("Member ID is required. Use --id=123");
            return 1;
        }

        try {
            $stmt = $this->db->prepare('DELETE FROM member WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                $this->error("Member with ID $id not found");
                return 1;
            }

            $this->success("Member $id deleted successfully");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to delete member: " . $e->getMessage());
            return 1;
        }
    }
}

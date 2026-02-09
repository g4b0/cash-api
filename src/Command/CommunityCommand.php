<?php

namespace App\Command;

class CommunityCommand extends AbstractCommand
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
        $name = $this->args['name'] ?? null;

        if (empty($name)) {
            $this->error("Community name is required. Use --name=\"Community Name\"");
            return 1;
        }

        try {
            $stmt = $this->db->prepare('INSERT INTO community (name) VALUES (?)');
            $stmt->execute([$name]);

            $id = (int) $this->db->lastInsertId();
            $this->success("Community created with ID: $id");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create community: " . $e->getMessage());
            return 1;
        }
    }

    private function update(): int
    {
        $id = $this->args['id'] ?? null;
        $name = $this->args['name'] ?? null;

        if (empty($id)) {
            $this->error("Community ID is required. Use --id=123");
            return 1;
        }

        if (empty($name)) {
            $this->error("Community name is required. Use --name=\"New Name\"");
            return 1;
        }

        try {
            $stmt = $this->db->prepare('UPDATE community SET name = ? WHERE id = ?');
            $stmt->execute([$name, $id]);

            if ($stmt->rowCount() === 0) {
                $this->error("Community with ID $id not found");
                return 1;
            }

            $this->success("Community $id updated successfully");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to update community: " . $e->getMessage());
            return 1;
        }
    }

    private function delete(): int
    {
        $id = $this->args['id'] ?? null;

        if (empty($id)) {
            $this->error("Community ID is required. Use --id=123");
            return 1;
        }

        try {
            $stmt = $this->db->prepare('DELETE FROM community WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                $this->error("Community with ID $id not found");
                return 1;
            }

            $this->success("Community $id deleted successfully");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to delete community: " . $e->getMessage());
            return 1;
        }
    }
}

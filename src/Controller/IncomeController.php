<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Validation\Validator;
use flight\Engine;
use PDO;

class IncomeController
{
    private Engine $app;
    private Validator $validator;

    public function __construct(Engine $app)
    {
        $this->app = $app;
        $this->validator = new Validator();
    }

    public function create(): void
    {
        // 1. Get authenticated user
        $authUser = $this->app->get('auth_user');
        $ownerId = (int) $authUser->sub;
        $communityId = (int) $authUser->cid;

        // 2. Verify member exists and belongs to community
        $db = $this->app->get('db');
        $stmt = $db->prepare('SELECT contribution_percentage FROM member WHERE id = ? AND community_id = ?');
        $stmt->execute([$ownerId, $communityId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            throw AppException::FORBIDDEN();
        }

        // 3. Get request data
        $data = $this->app->request()->data;

        // 4. Validate required fields
        $amount = $this->validator->validateAmount($data->amount ?? null);
        $reason = $this->validator->validateReason($data->reason ?? null);
        $date = $this->validator->validateDate($data->date ?? null);

        // 5. Handle contribution_percentage (optional)
        $contributionPercentage = $this->validator->validateContributionPercentage($data->contribution_percentage ?? null);
        if ($contributionPercentage === null) {
            $contributionPercentage = (int) $member['contribution_percentage'];
        }

        // 6. Insert income record
        $stmt = $db->prepare('INSERT INTO income (owner_id, date, reason, amount, contribution_percentage) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$ownerId, $date, $reason, $amount, $contributionPercentage]);
        $incomeId = $db->lastInsertId();

        // 7. Fetch created record
        $stmt = $db->prepare('SELECT * FROM income WHERE id = ?');
        $stmt->execute([$incomeId]);
        $income = $stmt->fetch(PDO::FETCH_ASSOC);

        // 8. Return 201 Created
        $this->app->json($income, 201);
    }

    public function read(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->app->get('auth_user');
        $communityId = (int) $authUser->cid;

        // 2. Fetch income record
        $db = $this->app->get('db');
        $stmt = $db->prepare('SELECT * FROM income WHERE id = ?');
        $stmt->execute([$id]);
        $income = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$income) {
            throw AppException::INCOME_NOT_FOUND();
        }

        // 3. Verify community access (fetch owner's community)
        $stmt = $db->prepare('SELECT community_id FROM member WHERE id = ?');
        $stmt->execute([$income['owner_id']]);
        $ownerCommunityId = $stmt->fetchColumn();

        if ((int) $ownerCommunityId !== $communityId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Return record
        $this->app->json($income);
    }

    public function update(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->app->get('auth_user');
        $memberId = (int) $authUser->sub;

        // 2. Fetch income record
        $db = $this->app->get('db');
        $stmt = $db->prepare('SELECT * FROM income WHERE id = ?');
        $stmt->execute([$id]);
        $income = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$income) {
            throw AppException::INCOME_NOT_FOUND();
        }

        // 3. Verify ownership
        if ((int) $income['owner_id'] !== $memberId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Get request data and validate provided fields
        $data = $this->app->request()->data;
        $updates = [];
        $params = [];

        if (isset($data->amount)) {
            $updates[] = 'amount = ?';
            $params[] = $this->validator->validateAmount($data->amount);
        }
        if (isset($data->reason)) {
            $updates[] = 'reason = ?';
            $params[] = $this->validator->validateReason($data->reason);
        }
        if (isset($data->date)) {
            $updates[] = 'date = ?';
            $params[] = $this->validator->validateDate($data->date);
        }
        if (isset($data->contribution_percentage)) {
            $updates[] = 'contribution_percentage = ?';
            $params[] = $this->validator->validateContributionPercentage($data->contribution_percentage);
        }

        // 5. Execute update if fields provided
        if (!empty($updates)) {
            $params[] = $id;
            $sql = 'UPDATE income SET ' . implode(', ', $updates) . ' WHERE id = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }

        // 6. Fetch updated record
        $stmt = $db->prepare('SELECT * FROM income WHERE id = ?');
        $stmt->execute([$id]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);

        // 7. Return updated record
        $this->app->json($updated);
    }

    public function delete(string $id): void
    {
        // 1. Get authenticated user
        $authUser = $this->app->get('auth_user');
        $memberId = (int) $authUser->sub;

        // 2. Fetch income record
        $db = $this->app->get('db');
        $stmt = $db->prepare('SELECT owner_id FROM income WHERE id = ?');
        $stmt->execute([$id]);
        $income = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$income) {
            throw AppException::INCOME_NOT_FOUND();
        }

        // 3. Verify ownership
        if ((int) $income['owner_id'] !== $memberId) {
            throw AppException::FORBIDDEN();
        }

        // 4. Delete record
        $stmt = $db->prepare('DELETE FROM income WHERE id = ?');
        $stmt->execute([$id]);

        // 5. Return 204 No Content
        $this->app->json(null, 204);
    }
}

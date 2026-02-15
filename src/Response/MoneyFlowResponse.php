<?php

namespace App\Response;

use App\Response\AppResponse;

abstract class MoneyFlowResponse extends AppResponse
{
    public int $id;
    public int $ownerId;
    public \DateTime $date;
    public string $reason;
    public float $amount;
    public \DateTime $createdAt;
    public \DateTime $updatedAt;

    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->ownerId = (int) $data['owner_id'];
        $this->date = new \DateTime($data['date']);
        $this->reason = $data['reason'];
        $this->amount = (float) $data['amount'];
        $this->createdAt = new \DateTime($data['created_at']);
        $this->updatedAt = new \DateTime($data['updated_at']);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->ownerId,
            'date' => $this->date->format('Y-m-d'),
            'reason' => $this->reason,
            'amount' => (string) $this->amount,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
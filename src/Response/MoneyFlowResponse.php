<?php

namespace App\Response;

use App\Response\AppResponse;

abstract class MoneyFlowResponse extends AppResponse
{
    public int $id;
    public int $ownerId;
    public \DateTime $date;
    public string $reason;
    public string $amount; // stored as string for JSON precision
    public \DateTime $createdAt;
    public \DateTime $updatedAt;

    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->ownerId = (int) $data['ownerId'];
        $this->date = new \DateTime($data['date']);
        $this->reason = $data['reason'];
        $this->amount = (string) $data['amount'];
        $this->createdAt = new \DateTime($data['createdAt']);
        $this->updatedAt = new \DateTime($data['updatedAt']);
    }
}
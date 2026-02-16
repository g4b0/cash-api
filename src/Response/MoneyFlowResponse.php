<?php

namespace App\Response;

use App\Response\AppResponse;

abstract class MoneyFlowResponse extends AppResponse
{
    public int $id;
    public int $memberId;
    public \DateTime $date;
    public string $reason;
    public float $amount;
    public \DateTime $createdAt;
    public \DateTime $updatedAt;

    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->memberId = (int) $data['memberId'];
        $this->date = new \DateTime($data['date']);
        $this->reason = $data['reason'];
        $this->amount = (float) $data['amount'];
        $this->createdAt = new \DateTime($data['createdAt']);
        $this->updatedAt = new \DateTime($data['updatedAt']);
    }
}
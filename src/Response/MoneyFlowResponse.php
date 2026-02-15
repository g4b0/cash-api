<?php

namespace App\Response;

use App\Response\AppResponse;

abstract class MoneyFlowResponse extends AppResponse
{
    public int $id;
    public int $owner_id;
    public \DateTime $date;
    public string $reason;
    public string $amount; // stored as string for JSON precision
    public \DateTime $created_at;
    public \DateTime $updated_at;

    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->owner_id = (int) $data['owner_id'];
        $this->date = new \DateTime($data['date']);
        $this->reason = $data['reason'];
        $this->amount = (string) $data['amount'];
        $this->created_at = new \DateTime($data['created_at']);
        $this->updated_at = new \DateTime($data['updated_at']);
    }
}
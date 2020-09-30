<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

class Payment
{
    private string $id;
    private Amount $amount;
    private PaymentStatus $status;

    public function __construct(string $id, Amount $amount, PaymentStatus $status)
    {
        $this->id = $id;
        $this->amount = $amount;
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }
}

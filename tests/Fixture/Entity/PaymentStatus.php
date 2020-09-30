<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

use DateTimeImmutable;

class PaymentStatus
{
    private string $paymentId;
    private string $status;
    private DateTimeImmutable $at;

    public function __construct(string $paymentId, string $status, DateTimeImmutable $at)
    {
        $this->paymentId = $paymentId;
        $this->status = $status;
        $this->at = $at;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAt(): DateTimeImmutable
    {
        return $this->at;
    }
}

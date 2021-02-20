<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

class OrderStore
{
    private string $id;
    private string $name;
    private string $orderId;

    public function __construct(string $id, string $orderId, string $name)
    {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->name = $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

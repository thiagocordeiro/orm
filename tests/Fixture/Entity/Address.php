<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

use DateTimeImmutable;

class Address
{
    private string $id;
    private string $street;
    private string $number;
    private DateTimeImmutable $createdAt;

    public function __construct(string $id, string $street, string $number, DateTimeImmutable $createdAt)
    {
        $this->id = $id;
        $this->street = $street;
        $this->number = $number;
        $this->createdAt = $createdAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

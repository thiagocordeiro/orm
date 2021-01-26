<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

use DateTimeImmutable;

class Address
{
    private string $id;
    private string $street;
    private string $number;
    private bool $main;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        string $id,
        string $street,
        string $number,
        bool $main,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $deletedAt = null
    ) {
        $this->id = $id;
        $this->street = $street;
        $this->number = $number;
        $this->main = $main;
        $this->createdAt = $createdAt;
        $this->deletedAt = $deletedAt;
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

    public function main(): bool
    {
        return $this->main;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}

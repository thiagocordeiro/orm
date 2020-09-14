<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

use DateTimeImmutable;

class Address
{
    private string $id;
    private string $street;
    private string $number;
    private DateTimeImmutable $createAt;

    public function __construct(string $id, string $street, string $number, DateTimeImmutable $createAt)
    {
        $this->id = $id;
        $this->street = $street;
        $this->number = $number;
        $this->createAt = $createAt;
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

    public function getCreateAt(): DateTimeImmutable
    {
        return $this->createAt;
    }
}

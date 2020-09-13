<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

class Address
{
    private string $id;
    private string $street;
    private string $number;

    public function __construct(string $id, string $street, string $number)
    {
        $this->id = $id;
        $this->street = $street;
        $this->number = $number;
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
}

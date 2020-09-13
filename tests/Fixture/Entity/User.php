<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

use Test\Orm\Fixture\Vo\Age;
use Test\Orm\Fixture\Vo\Email;
use Test\Orm\Fixture\Vo\Height;

class User
{
    private string $id;
    private Email $email;
    private Height $height;
    private Age $age;
    private bool $active;
    private Address $address;

    public function __construct(string $id, Email $email, Height $height, Age $age, bool $active, Address $address)
    {
        $this->id = $id;
        $this->email = $email;
        $this->height = $height;
        $this->age = $age;
        $this->active = $active;
        $this->address = $address;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getHeight(): Height
    {
        return $this->height;
    }

    public function getAge(): Age
    {
        return $this->age;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}

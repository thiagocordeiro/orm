<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

use Test\Orm\Fixture\Vo\Email;
use Test\Orm\Fixture\Vo\Height;

class NullableProperty
{
    private string $id;
    private ?Email $email;
    private ?Height $height;
    private ?Amount $amount;

    public function __construct(string $id, ?Email $email, ?Height $height, ?Amount $amount)
    {
        $this->id = $id;
        $this->email = $email;
        $this->height = $height;
        $this->amount = $amount;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function getHeight(): ?Height
    {
        return $this->height;
    }

    public function getAmount(): ?Amount
    {
        return $this->amount;
    }
}

<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

use Test\Orm\Fixture\Vo\Amount;

class Product
{
    private string $id;
    private Amount $price;

    public function __construct(string $id, Amount $price)
    {
        $this->id = $id;
        $this->price = $price;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPrice(): Amount
    {
        return $this->price;
    }
}

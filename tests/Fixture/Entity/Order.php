<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

use Test\Orm\Fixture\Vo\Amount;
use Test\Orm\Fixture\Vo\OrderProduct;

class Order
{
    private string $id;
    private User $user;
    private Amount $total;

    /** @var OrderProduct[] */
    private array $products;

    public function __construct(string $id, User $user, Amount $total, OrderProduct ...$products)
    {
        $this->id = $id;
        $this->user = $user;
        $this->total = $total;
        $this->products = $products;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTotal(): Amount
    {
        return $this->total;
    }

    /**
     * @return OrderProduct[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }
}

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

    /** @var OrderStore[] */
    private array $stores;

    /**
     * @param OrderProduct[] $products
     * @param OrderStore[] $stores
     */
    public function __construct(string $id, User $user, Amount $total, array $products, array $stores)
    {
        $this->id = $id;
        $this->user = $user;
        $this->total = $total;
        $this->products = $products;
        $this->stores = $stores;
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

    /**
     * @return OrderStore[]
     */
    public function getStores(): array
    {
        return $this->stores;
    }
}

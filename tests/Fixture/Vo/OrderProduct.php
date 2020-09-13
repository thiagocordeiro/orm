<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Vo;

use Test\Orm\Fixture\Entity\Product;

class OrderProduct
{
    private float $quantity;
    private string $orderId;
    private Product $product;
    private Amount $price;

    public function __construct(float $quantity, string $orderId, Product $product, Amount $price)
    {
        $this->quantity = $quantity;
        $this->product = $product;
        $this->orderId = $orderId;
        $this->price = $price;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getPrice(): Amount
    {
        return $this->price;
    }
}

<?php

declare(strict_types=1);

namespace Test\Orm\Integration;

use DateTimeImmutable;
use Orm\Repository;
use Test\Orm\Config\IntegrationTestCase;
use Test\Orm\Fixture\Entity\Address;
use Test\Orm\Fixture\Entity\Order;
use Test\Orm\Fixture\Entity\OrderStore;
use Test\Orm\Fixture\Entity\Product;
use Test\Orm\Fixture\Entity\User;
use Test\Orm\Fixture\Vo\Age;
use Test\Orm\Fixture\Vo\Amount;
use Test\Orm\Fixture\Vo\Currency;
use Test\Orm\Fixture\Vo\Email;
use Test\Orm\Fixture\Vo\Height;
use Test\Orm\Fixture\Vo\OrderProduct;

class RepositoryTest extends IntegrationTestCase
{
    private const COLUMNS = <<<'STRING'
        `id`,
        `user_id`,
        `total_value`,
        `total_currency`
    STRING;
    private const BINDINGS = <<<'STRING'
        :id,
        :user_id,
        :total_value,
        :total_currency
    STRING;
    private const COLUMNS_EQUAL_BINDINGS = <<<'STRING'
        `id` = :id,
        `user_id` = :user_id,
        `total_value` = :total_value,
        `total_currency` = :total_currency
    STRING;

    /** @var Repository<Order> */
    private Repository $repository;

    private Order $order;
    private DateTimeImmutable $now;
    private Amount $price;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = new DateTimeImmutable('2021-02-20 13:02:20 UTC');
        $this->price = new Amount(100, Currency::EUR());

        $this->repository = $this->em->getRepository(Order::class);
        $address = new Address('address-1', 'Centraal Station Straat', '1234', true, $this->now);
        $user = new User('user-1', new Email('thiago@thiago.com'), new Height(1.75), new Age(31), true, $address);
        $this->order = new Order(
            'order-1',
            $user,
            new Amount(1.0e-5, Currency::EUR()),
            [new OrderProduct(1, 'order-1', new Product('product-001', $this->price), $this->price)],
            [new OrderStore('store-xxx', 'order-1', 'Store XXX')],
        );
    }

    public function testColumns(): void
    {
        $this->assertEquals(self::COLUMNS, $this->repository->getColumns());
    }

    public function testBindings(): void
    {
        $this->assertEquals(self::BINDINGS, $this->repository->getBindings());
    }

    public function testColumnsEqualToBindings(): void
    {
        $this->assertEquals(self::COLUMNS_EQUAL_BINDINGS, $this->repository->getColumnsEqualBindings());
    }

    public function testDeleteCriteria(): void
    {
        $this->assertEquals(['id' => 'order-1'], $this->repository->getDeleteCriteria($this->order));
    }

    public function testEntityToDatabaseRow(): void
    {
        $this->assertEquals(
            [
                'id' => 'order-1',
                'user_id' => 'user-1',
                'total_value' => '0.0000100000',
                'total_currency' => 'EUR',
            ],
            $this->repository->entityToDatabaseRow($this->order),
        );
    }

    public function testDatabaseRowToEntity(): void
    {
        $this->em->getRepository(User::class)->insert($this->order->getUser());
        $this->em->getRepository(Address::class)->insert($this->order->getUser()->getAddress());
        $this->em->getRepository(Product::class)->insert(new Product('product-001', $this->price));
        $this->em->getRepository(OrderProduct::class)->insert(...$this->order->getProducts());
        $this->em->getRepository(OrderStore::class)->insert(...$this->order->getStores());

        $this->assertEquals(
            $this->order,
            $this->repository->databaseRowToEntity(
                [
                    'id' => 'order-1',
                    'user_id' => 'user-1',
                    'total_value' => '0.0000100000',
                    'total_currency' => 'EUR',
                ],
            ),
        );
    }

    public function testExists(): void
    {
        $repository = $this->em->getRepository(User::class);

        $repository->insert($this->order->getUser());

        $this->assertTrue($repository->exists(['id' => 'user-1']));
        $this->assertFalse($repository->exists(['id' => 'foo-bar']));
    }

    public function testLoadByQuery(): void
    {
        $repository = $this->em->getRepository(Product::class);
        $repository->insert(
            new Product('product-001', $this->price),
            new Product('product-002', $this->price),
            new Product('product-xxx', $this->price),
            new Product('product-003', $this->price),
        );

        $product1 = $repository->loadByQuery('select * from products where id = :id', ['id' => 'product-xxx']);
        $product2 = $repository->loadByQuery('select * from products where id = :id', ['id' => 'product-abc']);

        $this->assertEquals(new Product('product-xxx', $this->price), $product1);
        $this->assertNull($product2);
    }

    public function testSelectByQuery(): void
    {
        $repository = $this->em->getRepository(Product::class);
        $repository->insert(
            new Product('product-001', $this->price),
            new Product('product-002', $this->price),
            new Product('product-xxx', $this->price),
            new Product('product-003', $this->price),
        );

        $products = $repository->selectByQuery('select * from products where id like :id', ['id' => 'product-00%']);

        $this->assertEquals(
            [
                new Product('product-001', $this->price),
                new Product('product-002', $this->price),
                new Product('product-003', $this->price),
            ],
            iterator_to_array($products),
        );
    }
}

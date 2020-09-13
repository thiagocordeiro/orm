<?php

declare(strict_types=1);

namespace Test\Orm\Integration;

use Orm\Connection;
use Orm\Exception\ClassMustHaveAConstructor;
use Orm\RepositoryFactory;
use PHPUnit\Framework\TestCase;
use Test\Orm\Fixture\Entity\Address;
use Test\Orm\Fixture\Entity\Order;
use Test\Orm\Fixture\Entity\Product;
use Test\Orm\Fixture\Entity\User;
use Test\Orm\Fixture\Vo\Age;
use Test\Orm\Fixture\Vo\Amount;
use Test\Orm\Fixture\Vo\Email;
use Test\Orm\Fixture\Vo\Height;
use Test\Orm\Fixture\Vo\OrderProduct;
use Throwable;

class RepositoryFactoryTest extends TestCase
{
    private RepositoryFactory $factory;

    protected function setUp(): void
    {
        $db = __DIR__ . '/../../var/test.sqlite';
        $dsn = sprintf('sqlite:%s', $db);

        if (file_exists($db)) {
            unlink($db);
        }

        $connection = new Connection($dsn);
        $connection->exec(file_get_contents(__DIR__ . '/../Fixture/database.sql'));

        $this->factory = new RepositoryFactory($connection, 'var/cache/orm/', true);
    }

    /**
     * @throws ClassMustHaveAConstructor
     * @throws Throwable
     */
    public function testInsertAndRetrieveOrder(): void
    {
        $address = new Address('address-1', 'Centraal Station Straat', '1234');
        $user = new User('user-1', new Email('thiago@thiago.com'), new Height(1.75), new Age(31), true, $address);
        $product1 = new Product('prod-1', new Amount(100, 'BRL'));
        $product2 = new Product('prod-2', new Amount(20, 'BRL'));

        $orderProduct1 = new OrderProduct(2, 'order-1', $product1, $product1->getPrice());
        $orderProduct2 = new OrderProduct(5, 'order-1', $product2, $product1->getPrice());
        $order = new Order('order-1', $user, new Amount(300, 'BRL'), ...[$orderProduct1, $orderProduct2]);

        $this->factory->getRepository(Address::class)->insert($address);
        $this->factory->getRepository(User::class)->insert($user);
        $this->factory->getRepository(Product::class)->insert($product1);
        $this->factory->getRepository(Product::class)->insert($product2);
        $this->factory->getRepository(Order::class)->insert($order);
        $this->factory->getRepository(OrderProduct::class)->insert($orderProduct1);
        $this->factory->getRepository(OrderProduct::class)->insert($orderProduct2);

        $loaded = $this->factory->getRepository(Order::class)->loadById('order-1');

        $this->assertEquals($order, $loaded);
    }

    public function testDeleteAddress(): void
    {
        $repository = $this->factory->getRepository(Address::class);
        $address = new Address('address-1', 'Centraal Station Straat', '1234');
        $repository->insert($address);

        $repository->delete($address);

        $this->assertNull($repository->loadById('address-1'));
    }

    public function testUpdateAddress(): void
    {
        $repository = $this->factory->getRepository(Address::class);
        $repository->insert(new Address('address-1', 'Centraal Station Straat', '1234'));

        $updated = new Address('address-1', 'Zuid Straat', '1234');
        $repository->update($updated);

        $this->assertEquals($updated, $repository->loadById('address-1'));
    }

    public function testLoadAddressBy(): void
    {
        $repository = $this->factory->getRepository(Address::class);
        $repository->insert(new Address('address-1', 'Centraal Station Straat', '1234'));
        $repository->insert(new Address('address-2', 'Leidseplein', '500'));
        $repository->insert(new Address('address-3', 'Leidseplein', '900'));
        $repository->insert(new Address('address-4', 'Keizersgracht', '1200'));

        $entity = $repository->loadBy(['number' => '500']);

        $this->assertEquals(new Address('address-2', 'Leidseplein', '500'), $entity);
    }

    public function testSelectAddressBy(): void
    {
        $repository = $this->factory->getRepository(Address::class);
        $repository->insert(new Address('address-1', 'Centraal Station Straat', '1234'));
        $repository->insert(new Address('address-2', 'Leidseplein', '500'));
        $repository->insert(new Address('address-3', 'Leidseplein', '900'));
        $repository->insert(new Address('address-4', 'Keizersgracht', '1200'));

        $entities = $repository->selectBy(['street' => 'Leidseplein']);

        $this->assertEquals([
            new Address('address-2', 'Leidseplein', '500'),
            new Address('address-3', 'Leidseplein', '900'),
        ], iterator_to_array($entities));
    }

    public function testSelectLimitedAddress(): void
    {
        $repository = $this->factory->getRepository(Address::class);
        $repository->insert(new Address('address-1', 'Centraal Station Straat', '1234'));
        $repository->insert(new Address('address-2', 'Leidseplein', '500'));
        $repository->insert(new Address('address-3', 'Leidseplein', '900'));
        $repository->insert(new Address('address-4', 'Keizersgracht', '1200'));

        $entities = $repository->select('addresses', [], 'id desc', 2);

        $this->assertEquals([
            new Address('address-4', 'Keizersgracht', '1200'),
            new Address('address-3', 'Leidseplein', '900'),
        ], iterator_to_array($entities));
    }
}

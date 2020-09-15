<?php

declare(strict_types=1);

namespace Test\Orm\Integration;

use DateTimeImmutable;
use Orm\Connection;
use Orm\EntityManager;
use Orm\Exception\ClassMustHaveAConstructor;
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
    private EntityManager $em;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2020-09-14 22:25:30');

        $db = __DIR__ . '/../../var/test.sqlite';
        $dsn = sprintf('sqlite:%s', $db);

        if (file_exists($db)) {
            unlink($db);
        }

        $connection = new Connection($dsn);
        $connection->exec((string) file_get_contents(__DIR__ . '/../Fixture/database.sql'));

        $this->em = new EntityManager($connection, 'var/cache/orm/', true);
    }

    /**
     * @throws ClassMustHaveAConstructor
     * @throws Throwable
     */
    public function testInsertAndRetrieveOrder(): void
    {
        $address = new Address('address-1', 'Centraal Station Straat', '1234', $this->now);
        $user = new User('user-1', new Email('thiago@thiago.com'), new Height(1.75), new Age(31), true, $address);
        $product1 = new Product('prod-1', new Amount(100, 'BRL'));
        $product2 = new Product('prod-2', new Amount(20, 'BRL'));

        $orderProduct1 = new OrderProduct(2, 'order-1', $product1, $product1->getPrice());
        $orderProduct2 = new OrderProduct(5, 'order-1', $product2, $product1->getPrice());
        $order = new Order('order-1', $user, new Amount(300, 'BRL'), ...[$orderProduct1, $orderProduct2]);

        $this->em->getRepository(Address::class)->insert($address);
        $this->em->getRepository(User::class)->insert($user);
        $this->em->getRepository(Product::class)->insert($product1);
        $this->em->getRepository(Product::class)->insert($product2);
        $this->em->getRepository(Order::class)->insert($order);
        $this->em->getRepository(OrderProduct::class)->insert($orderProduct1);
        $this->em->getRepository(OrderProduct::class)->insert($orderProduct2);

        $loaded = $this->em->getRepository(Order::class)->loadById('order-1');

        $this->assertEquals($order, $loaded);
    }

    /**
     * @throws Throwable
     */
    public function testDeleteAddress(): void
    {
        $repository = $this->em->getRepository(Address::class);
        $address = new Address('address-1', 'Centraal Station Straat', '1234', $this->now);
        $repository->insert($address);

        $repository->delete($address);

        $this->assertNull($repository->loadById('address-1'));
    }

    /**
     * @throws Throwable
     */
    public function testUpdateAddress(): void
    {
        $repository = $this->em->getRepository(Address::class);
        $repository->insert(new Address('address-1', 'Centraal Station Straat', '1234', $this->now));

        $updated = new Address('address-1', 'Zuid Straat', '1234', $this->now);
        $repository->update($updated);

        $this->assertEquals($updated, $repository->loadById('address-1'));
    }

    /**
     * @throws Throwable
     */
    public function testLoadAddressBy(): void
    {
        $repository = $this->em->getRepository(Address::class);
        $repository->insert(new Address('address-1', 'Centraal Station Straat', '1234', $this->now));
        $repository->insert(new Address('address-2', 'Leidseplein', '500', $this->now));
        $repository->insert(new Address('address-3', 'Leidseplein', '900', $this->now));
        $repository->insert(new Address('address-4', 'Keizersgracht', '1200', $this->now));

        $entity = $repository->loadBy(['number' => '500']);

        $this->assertEquals(new Address('address-2', 'Leidseplein', '500', $this->now), $entity);
    }

    /**
     * @throws Throwable
     */
    public function testSelectAddressBy(): void
    {
        $repository = $this->em->getRepository(Address::class);
        $repository->insert(new Address('address-1', 'Centraal Station Straat', '1234', $this->now));
        $repository->insert(new Address('address-2', 'Leidseplein', '500', $this->now));
        $repository->insert(new Address('address-3', 'Leidseplein', '900', $this->now));
        $repository->insert(new Address('address-4', 'Keizersgracht', '1200', $this->now));

        $entities = $repository->selectBy(['street' => 'Leidseplein']);

        $this->assertEquals([
            new Address('address-2', 'Leidseplein', '500', $this->now),
            new Address('address-3', 'Leidseplein', '900', $this->now),
        ], iterator_to_array($entities));
    }

    /**
     * @throws Throwable
     */
    public function testSelectLimitedAddress(): void
    {
        $repository = $this->em->getRepository(Address::class);
        $repository->insert(new Address('address-1', 'Centraal Station Straat', '1234', $this->now));
        $repository->insert(new Address('address-2', 'Leidseplein', '500', $this->now));
        $repository->insert(new Address('address-3', 'Leidseplein', '900', $this->now));
        $repository->insert(new Address('address-4', 'Keizersgracht', '1200', $this->now));

        $entities = $repository->select('addresses', [], 'id desc', 2);

        $this->assertEquals([
            new Address('address-4', 'Keizersgracht', '1200', $this->now),
            new Address('address-3', 'Leidseplein', '900', $this->now),
        ], iterator_to_array($entities));
    }
}

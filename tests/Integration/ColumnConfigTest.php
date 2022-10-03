<?php

declare(strict_types=1);

namespace Test\Orm\Integration;

use DateTimeImmutable;
use Test\Orm\Config\IntegrationTestCase;
use Test\Orm\Fixture\Entity\AccountHolder;
use Test\Orm\Fixture\Entity\Address;
use Test\Orm\Fixture\Entity\User;
use Test\Orm\Fixture\Vo\Age;
use Test\Orm\Fixture\Vo\Email;
use Test\Orm\Fixture\Vo\Height;
use Throwable;

class ColumnConfigTest extends IntegrationTestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = new DateTimeImmutable('2020-09-14 22:25:30');
    }

    /**
     * @throws Throwable
     */
    public function testInsertAccountHolder(): void
    {
        $this->createUser();

        $loaded = $this->em->getRepository(AccountHolder::class)->loadById('user-1');

        $this->assertEquals(new AccountHolder('user-1', 'thiago@thiago.com', true), $loaded);
    }

    /**
     * @throws Throwable
     */
    private function createUser(): void
    {
        $address = new Address('address-1', 'Street', '1234', true, $this->now);
        $email = new Email('thiago@thiago.com');
        $height = new Height(1.75);
        $age = new Age(31);
        $user = new User('user-1', $email, $height, $age, true, $address);

        $this->em->getRepository(User::class)->insert($user);
    }
}

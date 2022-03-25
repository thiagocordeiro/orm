<?php

declare(strict_types=1);

namespace Test\Orm\Integration;

use Test\Orm\Config\IntegrationTestCase;
use Test\Orm\Fixture\Entity\BankAccount;
use Test\Orm\Fixture\Enum\AccountType;

class BackedEnumTest extends IntegrationTestCase
{
    public function testSaveEntityWithBackedEnum(): void
    {
        $repository = $this->em->getRepository(BankAccount::class);
        $repository->insert(new BankAccount('checking', '123', AccountType::checking));
        $repository->insert(new BankAccount('saving', '123', AccountType::saving));

        $accounts = $repository->select();

        $this->assertEquals([
            new BankAccount('checking', '123', AccountType::checking),
            new BankAccount('saving', '123', AccountType::saving),
        ], iterator_to_array($accounts));
    }
}

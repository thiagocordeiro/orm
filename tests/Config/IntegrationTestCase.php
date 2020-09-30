<?php

namespace Test\Orm\Config;

use Orm\Connection;
use Orm\EntityManager;
use PHPUnit\Framework\TestCase;
use Test\Orm\Fixture\Entity\PaymentStatus;

class IntegrationTestCase extends TestCase
{
    protected EntityManager $em;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../../var/';
        $file = "{$path}test.sqlite";

        is_dir($path) ?: mkdir($path, 0777, true);
        file_put_contents($file, '');

        $connection = new Connection(sprintf('sqlite:%s', $file));
        $connection->exec((string) file_get_contents(__DIR__ . '/../Fixture/database.sql'));

        $this->em = new EntityManager(
            $connection,
            'var/cache/orm/',
            true,
            [
                PaymentStatus::class => ['table' => 'payment_status', 'order' => ['at' => 'desc']],
            ]
        );
    }
}

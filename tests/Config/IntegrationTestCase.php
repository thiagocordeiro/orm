<?php

declare(strict_types=1);

namespace Test\Orm\Config;

use Orm\Builder\RepositoryResolver;
use Orm\Connection;
use Orm\EntityManager;
use PHPUnit\Framework\TestCase;
use Test\Orm\Fixture\Entity\PaymentStatus;
use Test\Orm\Fixture\Entity\Post;

class IntegrationTestCase extends TestCase
{
    protected EntityManager $em;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../../var/';
        $file = "{$path}test.sqlite";

        is_dir($path) || mkdir($path, 0777, true);
        file_put_contents($file, '');

        $connection = new Connection(sprintf('sqlite:%s', $file));
        $connection->exec((string) file_get_contents(__DIR__ . '/../Fixture/database.sql'));

        $this->em = new EntityManager(
            connection: $connection,
            resolver: new RepositoryResolver(
                cacheDir: 'var/cache/orm/',
                pluralize: true,
                entityConfig: [
                    PaymentStatus::class => ['table' => 'payment_status', 'order' => ['at' => 'desc']],
                    Post::class => ['table' => 'posts', 'soft_delete' => 'true'],
                ],
            ),
        );
    }
}

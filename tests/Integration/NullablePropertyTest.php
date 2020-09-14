<?php

declare(strict_types=1);

namespace Test\Orm\Integration;

use Orm\Connection;
use Orm\RepositoryFactory;
use PHPUnit\Framework\TestCase;
use Test\Orm\Fixture\Entity\NullableProperty;
use Throwable;

class NullablePropertyTest extends TestCase
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
        $connection->exec((string) file_get_contents(__DIR__ . '/../Fixture/database.sql'));

        $this->factory = new RepositoryFactory($connection, 'var/cache/orm/', true);
    }

    /**
     * @throws Throwable
     */
    public function testSaveAndRetrieveWithNullValues(): void
    {
        $repository = $this->factory->getRepository(NullableProperty::class);
        $entity = new NullableProperty('nnn', null, null, null);

        $repository->insert($entity);

        $this->assertEquals($entity, $repository->loadById('nnn'));
    }
}

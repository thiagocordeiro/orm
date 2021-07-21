<?php

declare(strict_types=1);

namespace Orm;

use Orm\Builder\RepositoryResolver;
use Throwable;

class EntityManager
{
    private Connection $connection;
    private RepositoryResolver $resolver;

    public function __construct(Connection $connection, RepositoryResolver $resolver)
    {
        $this->connection = $connection;
        $this->resolver = $resolver;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param class-string<T> $class
     * @return Repository<T>
     * @throws Throwable
     * @template T of object
     */
    public function getRepository(string $class): Repository
    {
        $factory = $this->resolver->resolve($class);

        return $factory($this->connection, $this);
    }
}

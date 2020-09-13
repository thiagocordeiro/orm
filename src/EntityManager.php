<?php

declare(strict_types=1);

namespace Orm;

use Traversable;

abstract class EntityManager
{
    private Connection $connection;
    private RepositoryFactory $factory;

    /**
     * @param string|int $id
     */
    abstract public function loadById($id): ?object;

    /**
     * @param mixed[] $where
     */
    abstract public function loadBy(array $where): ?object;

    /**
     * @param mixed[] $where
     * @return Traversable<object>
     */
    abstract public function selectBy(array $where): Traversable;

    abstract public function insert(object $entity): void;

    abstract public function update(object $entity): void;

    abstract public function delete(object $entity): void;

    /**
     * @param mixed[] $item
     */
    abstract public function parseDataIntoObject(array $item): object;

    public function __construct(Connection $connection, RepositoryFactory $factory)
    {
        $this->connection = $connection;
        $this->factory = $factory;
    }

    public function connection(): Connection
    {
        return $this->connection;
    }

    public function factory(): RepositoryFactory
    {
        return $this->factory;
    }

    /**
     * @param mixed[] $where
     * @return Traversable<object>
     */
    public function select(
        string $from,
        array $where,
        string $order = '',
        ?int $limit = null,
        ?int $offset = null
    ): Traversable {
        $items = $this->connection()->select($from, $where, $order, $limit, $offset);

        foreach ($items as $item) {
            yield $this->parseDataIntoObject($item);
        }
    }

    /**
     * @param mixed[] $where
     */
    public function selectOne(string $from, array $where, string $orderBy = ''): ?object
    {
        $result = $this->connection()->select($from, $where, $orderBy, 1);
        $items = iterator_to_array($result);

        if (!$items) {
            return null;
        }

        return $this->parseDataIntoObject(
            current($items)
        );
    }
}

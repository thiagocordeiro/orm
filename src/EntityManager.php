<?php

declare(strict_types=1);

namespace Orm;

use Traversable;

/**
 * @template T
 */
abstract class EntityManager
{
    private Connection $connection;
    private RepositoryFactory $factory;

    /**
     * @param string|int $id
     * @return T|null
     */
    abstract public function loadById($id): ?object;

    /**
     * @param mixed[] $where
     * @return T|null
     */
    abstract public function loadBy(array $where): ?object;

    /**
     * @param mixed[] $where
     * @return Traversable<T>
     */
    abstract public function selectBy(array $where): Traversable;

    /**
     * @param T $entity
     */
    abstract public function insert(object $entity): void;

    /**
     * @param T $entity
     */
    abstract public function update(object $entity): void;

    /**
     * @param T $entity
     */
    abstract public function delete(object $entity): void;

    /**
     * @param mixed[] $item
     * @return T|null
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
     * @return Traversable<T>
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
     * @return T|null
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

<?php

declare(strict_types=1);

namespace Orm;

use Throwable;
use Traversable;

/**
 * @template T of object
 */
abstract class Repository
{
    protected Connection $connection;
    protected EntityManager $em;

    abstract public function getTable(): string;

    /**
     * @param mixed[] $where
     * @param array<string, string> $order
     * @return T|null
     */
    abstract public function loadBy(array $where, array $order = []): ?object;

    /**
     * @param mixed[] $where
     * @param array<string, string> $order
     * @return Traversable<T>
     */
    abstract public function selectBy(
        array $where = [],
        array $order = [],
        ?int $limit = null,
        ?int $offset = null
    ): Traversable;

    /**
     * @param T $entities
     */
    abstract public function insert(object ...$entities): void;

    /**
     * @param T $entities
     */
    abstract public function update(object ...$entities): void;

    /**
     * @param T $entities
     */
    abstract public function delete(object ...$entities): void;

    /**
     * @param mixed[] $item
     * @return T
     */
    abstract public function parseDataIntoObject(array $item): object;

    final public function __construct(Connection $connection, EntityManager $factory)
    {
        $this->connection = $connection;
        $this->em = $factory;
    }

    /**
     * @param string|int $id
     * @return T|null
     */
    public function loadById($id): ?object
    {
        return $this->loadBy(['id' => $id]);
    }

    /**
     * @param mixed[] $where
     * @param array<string, string> $order
     * @return Traversable<T>
     * @throws Throwable
     */
    public function select(
        string $from,
        array $where,
        array $order = [],
        ?int $limit = null,
        ?int $offset = null
    ): Traversable {
        $items = $this->connection->select($from, $where, $order, $limit, $offset);

        foreach ($items as $item) {
            yield $this->parseDataIntoObject($item);
        }
    }

    /**
     * @param mixed[] $where
     * @param array<string, string> $order
     * @return T|null
     */
    public function selectOne(string $from, array $where, array $order = []): ?object
    {
        $result = $this->connection->select($from, $where, $order, 1);
        $items = iterator_to_array($result);

        if (!$items) {
            return null;
        }

        return $this->parseDataIntoObject(
            current($items)
        );
    }
}

<?php

declare(strict_types=1);

namespace Orm;

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
     * @param array<string, string> $order
     * @return array<string, string>
     */
    abstract public function getOrder(array $order): array;

    abstract public function getColumns(): string;

    abstract public function getBindings(): string;

    abstract public function getColumnsEqualBindings(): string;

    /**
     * @param T $entity
     * @return array<string, mixed>
     */
    abstract public function getDeleteCriteria(object $entity): array;

    /**
     * @param T $entity
     * @return array<string, mixed>
     */
    abstract public function entityToDatabaseRow(object $entity): array;

    /**
     * @param mixed[] $item
     * @return T
     */
    abstract public function databaseRowToEntity(array $item): object;

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
        return $this->selectOne(['id' => $id]);
    }

    /**
     * @param array<string, string|int|float|bool|null> $where
     * @param array<string, string> $order
     * @return T|null
     */
    public function loadBy(array $where, array $order = []): ?object
    {
        return $this->selectOne($where, $this->getOrder($order));
    }

    /**
     * @param array<string, string|int|float|bool|null> $where
     * @param array<string, string> $order
     * @return Traversable<T>
     */
    public function select(array $where = [], array $order = [], ?int $limit = null, ?int $offset = null): Traversable
    {
        $items = $this->connection->select($this->getTable(), $where, $this->getOrder($order), $limit, $offset);

        foreach ($items as $item) {
            yield $this->databaseRowToEntity($item);
        }
    }

    /**
     * @param array<string, string|int|float|bool|null> $where
     * @param array<string, string> $order
     * @return T|null
     */
    public function selectOne(array $where, array $order = []): ?object
    {
        $result = $this->connection->select($this->getTable(), $where, $this->getOrder($order), 1);
        $items = iterator_to_array($result);

        if (!$items) {
            return null;
        }

        return $this->databaseRowToEntity(
            current($items)
        );
    }

    /**
     * @param T ...$entities
     */
    public function insert(object ...$entities): void
    {
        $statement = "
            INSERT INTO {$this->getTable()} (
                {$this->getColumns()}
            ) values (
                {$this->getBindings()}
            );
        ";

        foreach ($entities as $entity) {
            $this->connection->execute($statement, $this->entityToDatabaseRow($entity));
        }
    }

    /**
     * @param T ...$entities
     */
    public function update(object ...$entities): void
    {
        $statement = "
            UPDATE {$this->getTable()} SET
                {$this->getColumnsEqualBindings()}
            WHERE id = :id
        ";

        foreach ($entities as $entity) {
            $this->connection->execute($statement, $this->entityToDatabaseRow($entity));
        }
    }

    /**
     * @param T ...$entities
     */
    public function delete(object ...$entities): void
    {
        $statement = "DELETE FROM {$this->getTable()} WHERE id = :id";

        foreach ($entities as $entity) {
            $this->connection->execute($statement, $this->getDeleteCriteria($entity));
        }
    }
}

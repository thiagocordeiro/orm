<?php

declare(strict_types=1);

namespace Orm;

use Exception;
use ICanBoogie\Inflector;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

class Connection
{
    protected ?PDO $pdo = null;
    private int $transactionDepth = 0;
    private string $dsn;
    private ?string $user;
    private ?string $password;

    /** @var mixed[] */
    private array $options;

    /**
     * @param mixed[] $options
     */
    public function __construct(string $dsn, ?string $user = null, ?string $password = null, array $options = [])
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
        $this->options = $options;
    }

    public function pdo(): PDO
    {
        if (null === $this->pdo) {
            $this->pdo = new PDO($this->dsn, $this->user, $this->password, $this->options);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
        }

        return $this->pdo;
    }

    /**
     * @param array<string, string|int|float|bool|null> $where
     * @param array<string, string> $order
     * @return PDOStatement<mixed>
     * @throws Throwable
     */
    public function select(
        string $table,
        array $where = [],
        array $order = [],
        ?int $limit = null,
        ?int $offset = null,
    ): PDOStatement {
        [$_where, $_whereParams] = $this->getWhere($where);

        $stmt = $this->pdo()->prepare(
            sprintf(
                'select * from %s %s %s %s %s',
                $table,
                $_where,
                $this->getOrder($order),
                $limit ? "limit {$limit}" : '',
                $offset ? "offset {$offset}" : '',
            ),
        );

        $stmt->execute($_whereParams);

        return $stmt;
    }

    /**
     * @param array<string, string|int|float|bool|null> $values
     */
    public function insert(string $table, array $values): void
    {
        $_fields = [];

        foreach (array_keys($values) as $key) {
            $_fields[sprintf('`%s`', $key)] = sprintf(':%s', $key);
        }

        $stmt = $this->pdo()->prepare(
            sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $table,
                implode(', ', array_keys($_fields)),
                implode(', ', array_values($_fields)),
            ),
        );

        $stmt->execute($values);
    }

    /**
     * @param array<string, string|int|float|bool|null> $values
     * @param array<string, string|int|float|bool|null> $where
     */
    public function update(string $table, array $values, array $where): void
    {
        $_fields = array_map(fn (string $field) => sprintf('`%s` = :%s', $field, $field), array_keys($values));
        [$_where, $_params] = $this->getWhere($where);
        $_bindings = array_merge($values, $_params);

        $stmt = $this->pdo()->prepare(
            sprintf(
                'UPDATE %s SET %s %s',
                $table,
                implode(', ', $_fields),
                $_where,
            ),
        );

        $stmt->execute($_bindings);
    }

    /**
     * @param array<string, string|int|float|bool|null> $where
     */
    public function delete(string $table, array $where): void
    {
        [$_where, $_params] = $this->getWhere($where);

        $stmt = $this->pdo()->prepare(
            sprintf(
                'DELETE FROM %s %s',
                $table,
                $_where,
            ),
        );

        $stmt->execute($_params);
    }

    /**
     * @param array<string, string|int|float|bool|null> $where
     */
    public function count(string $table, array $where): int
    {
        [$_where, $_params] = $this->getWhere($where);

        return (int) $this->execute(
            sprintf(
                'select count(*) from %s %s',
                $table,
                $_where,
            ),
            $_params,
        )->fetchColumn();
    }

    /**
     * @param array<string|int, string|int|float|bool|null> $params
     * @return PDOStatement<mixed>
     */
    public function execute(string $statement, array $params = []): PDOStatement
    {
        $stmt = $this->pdo()->prepare($statement);
        $stmt->execute($params);

        return $stmt;
    }

    public function exec(string $statement): void
    {
        $this->pdo()->exec($statement);
    }

    /**
     * @throws Throwable
     */
    public function transaction(callable $fn): mixed
    {
        $this->beginTransaction();

        try {
            $res = $fn($this);
            $this->commit();

            return $res;
        } catch (Throwable $e) {
            $this->rollback();

            throw $e;
        }
    }

    public function beginTransaction(): void
    {
        $this->supportsNestedTransaction() === false || $this->transactionDepth === 0
            ? $this->pdo()->beginTransaction()
            : $this->exec("SAVEPOINT LEVEL{$this->transactionDepth}");

        $this->transactionDepth++;
    }

    public function commit(): void
    {
        $this->transactionDepth--;

        $this->supportsNestedTransaction() === false || $this->transactionDepth === 0
            ? $this->pdo()->commit()
            : $this->exec("RELEASE SAVEPOINT LEVEL{$this->transactionDepth}");
    }

    public function rollback(): void
    {
        if ($this->transactionDepth === 0) {
            throw new PDOException('Rollback error: There is no transaction started');
        }

        $this->transactionDepth--;

        $this->supportsNestedTransaction() === false || $this->transactionDepth === 0
            ? $this->pdo()->rollBack()
            : $this->exec("RELEASE SAVEPOINT LEVEL{$this->transactionDepth}");
    }

    public function close(): void
    {
        $this->pdo = null;
    }

    private function supportsNestedTransaction(): bool
    {
        return in_array($this->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME), ['mysql', 'pgsql'], true);
    }

    /**
     * @param array<string, string|int|float|bool|null> $conditions
     * @return mixed[]
     */
    private function getWhere(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $where = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            $column = Inflector::get()->underscore($column);
            $param = sprintf("w%s", $column);

            if (is_null($value)) {
                $where[] = sprintf('`%s` is null', $column);

                continue;
            }

            $where[] = sprintf('`%s` = :%s', $column, $param);
            $params[$param] = $value;
        }

        return [sprintf('WHERE %s', implode(' AND ', $where)), $params];
    }

    /**
     * @param array<string, string> $order
     * @throws Throwable
     */
    private function getOrder(array $order): string
    {
        if (count($order) === 0) {
            return '';
        }

        $ordering = [];

        foreach ($order as $field => $direction) {
            if (false === in_array(strtolower($direction), ['asc', 'desc'], true)) {
                throw new Exception(sprintf('Invalid sql ordering (%s %s)', $field, $direction));
            }

            $ordering[] = sprintf("%s %s", Inflector::get()->underscore($field), $direction);
        }

        return sprintf('ORDER BY %s', implode(', ', $ordering));
    }
}

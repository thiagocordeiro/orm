<?php

declare(strict_types=1);

namespace Orm;

use ICanBoogie\Inflector;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

class Connection
{
    protected PDO $pdo;
    private int $transactionDepth = 0;

    /**
     * @param mixed[] $options
     */
    public function __construct(string $dsn, ?string $user = null, ?string $password = null, array $options = [])
    {
        $this->pdo = new PDO($dsn, $user, $password, $options);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * @param mixed[] $conditions
     */
    public function select(
        string $table,
        array $conditions = [],
        string $order = '',
        ?int $limit = null,
        ?int $offset = null
    ): PDOStatement {
        [$where, $params] = $this->getWhere($conditions);

        $stmt = $this->pdo->prepare(
            sprintf(
                'select * from %s %s %s %s %s',
                $table,
                $where,
                $order ? "order by {$order}" : '',
                $limit ? "limit {$limit}" : '',
                $offset ? "offset {$offset}" : '',
            )
        );

        $stmt->execute($params);

        return $stmt;
    }

    /**
     * @param mixed[] $params
     */
    public function execute(string $statement, array $params = []): void
    {
        $stmt = $this->pdo->prepare($statement);
        $stmt->execute($params);
    }

    public function exec(string $statement): void
    {
        $this->pdo->exec($statement);
    }

    /**
     * @return mixed
     * @throws Throwable
     */
    public function transaction(callable $fn)
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
        $this->transactionDepth += 1;

        if ($this->supportsNestedTransaction() && $this->transactionDepth > 1) {
            $this->exec("SAVEPOINT LEVEL{$this->transactionDepth}");

            return;
        }

        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->transactionDepth -= 1;

        if ($this->supportsNestedTransaction() && $this->transactionDepth > 0) {
            $this->exec("RELEASE SAVEPOINT LEVEL{$this->transactionDepth}");

            return;
        }

        $this->pdo->commit();
    }

    public function rollback(): void
    {
        if ($this->transactionDepth === 0) {
            throw new PDOException('Rollback error: There is no transaction started');
        }

        $this->transactionDepth -= 1;

        if ($this->supportsNestedTransaction() && $this->transactionDepth > 0) {
            $this->exec("RELEASE SAVEPOINT LEVEL{$this->transactionDepth}");

            return;
        }

        $this->pdo->rollBack();
    }

    private function supportsNestedTransaction(): bool
    {
        return in_array($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME), ['mysql', 'pgsql']);
    }

    /**
     * @param mixed[] $conditions
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

            if (is_null($value)) {
                $where[] = sprintf('`%s` is null', $column);

                continue;
            }

            $where[] = sprintf('`%s` = :%s', $column, $column);
            $params[$column] = $value;
        }

        return [sprintf('where %s', implode(' AND ', $where)), $params];
    }
}

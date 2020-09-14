<?php

declare(strict_types=1);

namespace Orm;

use PDO;
use PDOStatement;

class Connection
{
    protected PDO $pdo;

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

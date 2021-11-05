<?php

declare(strict_types=1);

namespace Orm\Migration;

use Exception;
use Orm\Connection;

class DatabaseCreator
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(): void
    {
        $dsn = $this->connection->dsn();
        $url = str_replace(['mysql:', ';'], ['', '&'], $dsn);
        parse_str($url, $parsed);
        $dbname = $parsed['dbname'] ?? throw new Exception('Unable to parte dbname');
        $ndsn = str_replace(sprintf(';dbname=%s', $dbname), '', $dsn);

        $connection = new Connection($ndsn);
        $connection->execute("CREATE DATABASE IF NOT EXISTS `$dbname`;");
    }
}

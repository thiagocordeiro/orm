<?php

declare(strict_types=1);

namespace Orm\Migration;

use Orm\Connection;
use Traversable;

class Migrator
{
    private string $directory;
    private Connection $connection;

    public function __construct(string $directory, Connection $connection)
    {
        $this->directory = $directory;
        $this->connection = $connection;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @return Traversable<string>
     */
    public function migrate(): Traversable
    {
        $files = glob("{$this->directory}/*.php");

        if (empty($files)) {
            return;
        }

        $this->createMigrationTableIfNeeded();

        foreach ($files as $file) {
            $name = $this->run($file);

            if (null === $name) {
                continue;
            }

            yield $name;
        }
    }

    private function getMigration(string $file): Migration
    {
        $name = preg_replace('/[0-9]|.php/', '', "{$file}");
        $class = str_replace(' ', '', ucwords(str_replace('_', ' ', "{$name}")));

        $migration = new $class();
        assert($migration instanceof Migration);

        return $migration;
    }

    private function run(string $file): ?string
    {
        require_once $file;

        $name = basename($file);
        $executed = $this->connection->count('_migrations', ['name' => $name]) > 0;

        if ($executed) {
            return null;
        }

        $migration = $this->getMigration($name);
        $this->connection->exec($migration->up());
        $this->connection->insert('_migrations', ['name' => $name, 'executed_at' => date('Y-m-d H:i:s.u')]);

        return $name;
    }

    private function createMigrationTableIfNeeded(): void
    {
        $this->connection->exec(
            <<<SQL
                CREATE TABLE IF NOT EXISTS `_migrations` (
                  `name`        VARCHAR(255) PRIMARY KEY,
                  `executed_at` DATETIME(6)  NOT NULL
                );
            SQL,
        );
    }
}

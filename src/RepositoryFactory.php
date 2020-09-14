<?php

declare(strict_types=1);

namespace Orm;

use Orm\Builder\RepositoryTemplate;
use Orm\Builder\TableLayoutAnalyzer;
use Orm\Exception\ClassMustHaveAConstructor;
use Throwable;

class RepositoryFactory
{
    private Connection $connection;
    private string $cacheDir;
    private bool $pluralize;

    public function __construct(Connection $connection, string $cacheDir, bool $pluralize)
    {
        $this->connection = $connection;
        $this->cacheDir = $cacheDir;
        $this->pluralize = $pluralize;
    }

    /**
     * @throws ClassMustHaveAConstructor
     * @throws Throwable
     * @template T of object
     * @param class-string<T> $class
     * @return EntityManager<T>
     */
    public function getRepository(string $class): EntityManager
    {
        $repositoryClassName = str_replace('\\', '_', $class) . 'Repository';
        $repository = "Orm\\Repository\\{$repositoryClassName}";

        if (!class_exists($repository)) {
            $this->requireClass($repositoryClassName, $class);
        }

        return new $repository($this->connection, $this);
    }

    /**
     * @throws ClassMustHaveAConstructor
     * @throws Throwable
     */
    private function requireClass(string $repositoryName, string $class): void
    {
        $filePath = sprintf('%s/%s.php', $this->cacheDir, $repositoryName);

        if (false === file_exists($filePath)) {
            $this->createRepository($filePath, $repositoryName, $class);
        }

        require_once $filePath;
    }

    /**
     * @throws ClassMustHaveAConstructor
     * @throws Throwable
     */
    private function createRepository(string $filePath, string $repositoryName, string $class): void
    {
        $definition = (new TableLayoutAnalyzer($class, $this->pluralize))->analyze();
        $template = new RepositoryTemplate($definition, $repositoryName);

        is_dir($this->cacheDir) ?: mkdir($this->cacheDir, 0777, true);
        file_put_contents($filePath, (string) $template);
    }
}

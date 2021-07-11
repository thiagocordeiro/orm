<?php

declare(strict_types=1);

namespace Orm;

use Orm\Builder\RepositoryTemplate;
use Orm\Builder\TableLayoutAnalyzer;
use Orm\Exception\ClassMustHaveAConstructor;
use Throwable;

class EntityManager
{
    private Connection $connection;
    private string $cacheDir;
    private bool $pluralize;
    private ?string $fileUser;
    private ?string $fileGroup;

    /** @var mixed[] */
    private array $entityConfig;

    /**
     * @param mixed[] $entityConfig
     */
    public function __construct(
        Connection $connection,
        string $cacheDir,
        bool $pluralize,
        array $entityConfig = [],
        ?string $fileUser = null,
        ?string $fileGroup = null,
    ) {
        $this->connection = $connection;
        $this->cacheDir = $cacheDir;
        $this->pluralize = $pluralize;
        $this->entityConfig = $entityConfig;
        $this->fileUser = $fileUser;
        $this->fileGroup = $fileGroup;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param class-string<T> $class
     * @return Repository<T>
     * @throws ClassMustHaveAConstructor
     * @throws Throwable
     * @template T of object
     */
    public function getRepository(string $class): Repository
    {
        $repositoryClassName = str_replace('\\', '_', $class) . 'Repository';
        $repository = "Orm\\Repository\\{$repositoryClassName}";
        $entityConfig = $this->entityConfig[$class] ?? [];
        $entityRepository = $entityConfig['repository'] ?? null;

        if (null !== $entityRepository) {
            return new $entityRepository($this->connection, $this);
        }

        $factory = $entityConfig['factory'] ?? null;

        if (null !== $factory) {
            return $factory($this->connection, $this);
        }

        if (!class_exists($repository)) {
            $this->requireClass($repositoryClassName, $class);
        }

        return new $repository($this->connection, $this);
    }

    /**
     * @param class-string $class
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
     * @param class-string $class
     * @throws ClassMustHaveAConstructor
     * @throws Throwable
     */
    private function createRepository(string $filePath, string $repositoryName, string $class): void
    {
        $config = $this->entityConfig[$class] ?? [];
        $table = $config['table'] ?? null;

        $definition = (new TableLayoutAnalyzer($class, $this->pluralize, $table))->analyze();
        $template = new RepositoryTemplate($definition, $repositoryName, $config);

        if (false === is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
            $this->fixPermission($this->cacheDir);
        }

        file_put_contents($filePath, (string) $template);
        $this->fixPermission($filePath);
    }

    private function fixPermission(string $path): void
    {
        $this->fixUserPermission($path);
        $this->fixGroupPermission($path);
    }

    private function fixUserPermission(string $path): void
    {
        if (null === $this->fileUser) {
            return;
        }

        chown($path, $this->fileUser);
    }

    private function fixGroupPermission(string $path): void
    {
        if (null === $this->fileGroup) {
            return;
        }

        chgrp($path, $this->fileGroup);
    }
}

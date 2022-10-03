<?php

declare(strict_types=1);

namespace Orm\Builder;

use Orm\Connection;
use Orm\EntityManager;
use Throwable;

/**
 * @phpstan-type EntityConfig array{
 *      factory: ?callable,
 *      repository: ?class-string,
 *      table: ?string,
 *      order: ?array<string, string>,
 *      columns: ?array<string, string>,
 *      soft_delete: ?string,
 * }
 */
class RepositoryResolver
{
    private const EMPTY_ENTITY_CONFIG = [
        'factory' => null,
        'repository' => null,
        'table' => null,
        'order' => null,
        'columns' => null,
        'soft_delete' => null,
    ];

    private string $cacheDir;
    private bool $pluralize;
    private ?string $fileUser;
    private ?string $fileGroup;

    /** @var array<class-string, EntityConfig> */
    private array $entityConfig;

    /**
     * @param array<class-string, EntityConfig> $entityConfig
     */
    public function __construct(
        string $cacheDir,
        bool $pluralize,
        array $entityConfig = [],
        ?string $fileUser = null,
        ?string $fileGroup = null,
    ) {
        $this->cacheDir = $cacheDir;
        $this->pluralize = $pluralize;
        $this->entityConfig = $entityConfig;
        $this->fileUser = $fileUser;
        $this->fileGroup = $fileGroup;
    }

    /**
     * @param class-string $class
     * @throws Throwable
     */
    public function resolve(string $class): callable
    {
        $config = $this->entityConfig[$class] ?? self::EMPTY_ENTITY_CONFIG;
        $factory = $config['factory'] ?? null;

        if (null !== $factory) {
            return $factory;
        }

        $name = str_replace('\\', '_', $class) . 'Repository';
        $repository = $config['repository'] ?? "Orm\\Repository\\{$name}";

        if (false === class_exists($repository)) {
            $this->requireClass($name, $class);
        }

        return fn (Connection $conn, EntityManager $em) => new $repository($conn, $em);
    }

    /**
     * @param class-string $class
     * @throws Throwable
     */
    private function requireClass(string $name, string $class): void
    {
        $filePath = sprintf('%s/%s.php', $this->cacheDir, $name);

        if (false === file_exists($filePath)) {
            $this->createRepository($filePath, $name, $class);
        }

        require_once $filePath;
    }

    /**
     * @param class-string $class
     * @throws Throwable
     */
    private function createRepository(string $path, string $name, string $class): void
    {
        $config = $this->entityConfig[$class] ?? self::EMPTY_ENTITY_CONFIG;

        umask(0002);
        $table = $config['table'] ?? null;
        $columns = $config['columns'] ?? [];

        $definition = (new TableLayoutAnalyzer($class, $this->pluralize, $table, $columns))->analyze();
        $template = new RepositoryTemplate($definition, $name, $config);

        if (false === is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
            $this->fixPermission($this->cacheDir);
        }

        file_put_contents($path, (string) $template);
        $this->fixPermission($path);
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

        @chown($path, $this->fileUser);
    }

    private function fixGroupPermission(string $path): void
    {
        if (null === $this->fileGroup) {
            return;
        }

        @chgrp($path, $this->fileGroup);
    }
}

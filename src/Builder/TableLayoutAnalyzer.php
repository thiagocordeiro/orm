<?php

declare(strict_types=1);

namespace Orm\Builder;

use ICanBoogie\Inflector;
use ReflectionException;
use ReflectionParameter;
use Throwable;

class TableLayoutAnalyzer
{
    private ReflectionClass $class;
    private string $table;

    /** @var array<string, string> */
    private array $columns;

    /**
     * @param class-string $className
     * @param array<string, string> $columns
     * @throws ReflectionException
     */
    public function __construct(string $className, bool $pluralized, ?string $table = null, array $columns = [])
    {
        $class = new ReflectionClass($className);

        $this->class = $class;
        $this->table = $table ?? $this->resolveTableName($this->class->getShortName(), $pluralized);
        $this->columns = $columns;
    }

    /**
     * @throws Throwable
     */
    public function analyze(): TableDefinition
    {
        $properties = $this->class->getConstructor()->getParameters();

        $class = new ClassDefinition($this->class);
        $properties = array_map(
            function (ReflectionParameter $param) {
                $name = $param->getName();

                return new PropertyDefinition($this->class, $param, $this->columns[$name] ?? null);
            },
            $properties,
        );

        return new TableDefinition($class, $this->table, ...$properties);
    }

    private function resolveTableName(string $shortName, bool $pluralized): string
    {
        return Inflector::get()->underscore(
            $pluralized
                ? Inflector::get()->pluralize($shortName)
                : $shortName,
        );
    }
}

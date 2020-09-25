<?php

declare(strict_types=1);

namespace Orm\Builder;

use ICanBoogie\Inflector;
use Orm\Exception\ClassMustHaveAConstructor;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Throwable;

class TableLayoutAnalyzer
{
    private ReflectionClass $class;
    private string $table;

    /**
     * @throws ClassMustHaveAConstructor
     */
    public function __construct(string $className, bool $pluralized, ?string $table = null)
    {
        $class = (new BetterReflection())->classReflector()->reflect($className);
        $constructor = $class->getConstructor();

        if (!$constructor instanceof ReflectionMethod) {
            throw new ClassMustHaveAConstructor($className);
        }

        $this->class = $class;
        $this->table = $table ?? $this->resolveTableName($this->class->getShortName(), $pluralized);
    }

    /**
     * @throws Throwable
     */
    public function analyze(): TableDefinition
    {
        $properties = $this->class->getConstructor()->getParameters();

        $class = new ClassDefinition($this->class);
        $properties = array_map(
            fn (ReflectionParameter $param) => new PropertyDefinition($this->class, $param),
            $properties
        );

        return new TableDefinition($class, $this->table, ...$properties);
    }

    private function resolveTableName(string $shortName, bool $pluralized): string
    {
        return Inflector::get()->underscore(
            $pluralized
                ? Inflector::get()->pluralize($shortName)
                : $shortName
        );
    }
}

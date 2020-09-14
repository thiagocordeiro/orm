<?php

declare(strict_types=1);

namespace Orm\Builder;

use ArrayObject;
use DateTimeInterface;
use ICanBoogie\Inflector;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Throwable;
use Traversable;

class TableDefinition
{
    private ClassDefinition $class;
    private string $tableName;

    /** @var Traversable<TableField> */
    private Traversable $tableFields;

    /** @var Traversable<PropertyDefinition> */
    private Traversable $children;

    /**
     * @throws Throwable
     */
    public function __construct(bool $pluralized, ClassDefinition $class, PropertyDefinition ...$properties)
    {
        $this->class = $class;
        $this->tableName = $this->resolveTableName($pluralized, $class->getShortName());
        $this->tableFields = $this->resolveTableFields(...$properties);
        $this->children = new ArrayObject(array_filter($properties, fn (PropertyDefinition $prop) => $prop->isArray()));
    }

    public function getClass(): ClassDefinition
    {
        return $this->class;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return Traversable<TableField>
     */
    public function getObjectFields(): Traversable
    {
        return $this->tableFields;
    }

    /**
     * @return Traversable<TableField>
     */
    public function getTableFields(): Traversable
    {
        foreach ($this->tableFields as $field) {
            if ($field->isChild()) {
                continue;
            }

            yield $field;
        }
    }

    /**
     * @return Traversable<PropertyDefinition>
     */
    public function getChildren(): Traversable
    {
        return $this->children;
    }

    private function resolveTableName(bool $pluralized, string $shortName): string
    {
        return Inflector::get()->underscore(
            $pluralized
                ? Inflector::get()->pluralize($shortName)
                : $shortName
        );
    }

    /**
     * @throws Throwable
     */
    private function resolveTableFields(PropertyDefinition ...$properties): ArrayObject
    {
        $array = [];

        foreach ($properties as $property) {
            if ($property->isArray()) {
                $array[] = new TableField(
                    $property->getName(),
                    '',
                    'string',
                    $property,
                    null,
                    true
                );

                continue;
            }

            if ($property->isEntity()) {
                $array[] = new TableField(
                    $property->getName(),
                    sprintf('%s_id', $property->getName()),
                    $property->getIdType(),
                    $property->withGetter(sprintf('%s->getId()', $property->getGetter()))
                );

                continue;
            }

            if ($property->isValueObject()) {
                $array = array_merge($array, $this->getValueObjectFields($property));

                continue;
            }

            $array[] = new TableField($property->getName(), $property->getName(), $property->getType(), $property);
        }

        return new ArrayObject($array);
    }

    /**
     * @return TableField[]
     * @throws Throwable
     */
    private function getValueObjectFields(PropertyDefinition $voProperty): array
    {
        $classDefinition = $voProperty->getClassDefinition();

        if (null === $classDefinition) {
            return [];
        }

        $reflection = (new BetterReflection())->classReflector()->reflect($classDefinition->getName());

        if ($reflection->implementsInterface(DateTimeInterface::class)) {
            return [
                new TableField(
                    $voProperty->getName(),
                    $voProperty->getName(),
                    'datetime',
                    $voProperty->withGetter(sprintf('%s->format(\'Y-m-d H:i:s.u\')', $voProperty->getGetter())),
                    $voProperty->getType()
                ),
            ];
        }

        try {
            $properties = array_map(
                fn (ReflectionParameter $param) => new PropertyDefinition($reflection, $param),
                $reflection->getConstructor()->getParameters()
            );
        } catch (Throwable $e) {
            dd($voProperty);
        }

        if (count($properties) === 1) {
            $prop = current($properties);

            return [
                new TableField(
                    $voProperty->getName(),
                    $voProperty->getName(),
                    $prop->getType(),
                    $voProperty->withGetter(sprintf('%s->%s', $voProperty->getGetter(), $prop->getGetter())),
                    $voProperty->getType()
                ),
            ];
        }

        return array_map(
            fn (PropertyDefinition $prop) => new TableField(
                $voProperty->getName(),
                sprintf('%s_%s', $voProperty->getName(), $prop->getName()),
                $prop->getType(),
                $voProperty->withGetter(sprintf('%s->%s', $voProperty->getGetter(), $prop->getGetter())),
                $voProperty->getType(),
            ),
            $properties
        );
    }
}

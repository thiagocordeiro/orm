<?php

declare(strict_types=1);

namespace Orm\Builder;

use ArrayObject;
use DateTimeInterface;
use Exception;
use ReflectionParameter;
use Throwable;
use Traversable;

class TableDefinition
{
    private ClassDefinition $class;
    private string $tableName;

    /** @var Traversable<TableField> */
    private Traversable $tableFields;

    /**
     * @throws Throwable
     */
    public function __construct(ClassDefinition $class, string $table, PropertyDefinition ...$properties)
    {
        $this->class = $class;
        $this->tableName = $table;
        $this->tableFields = $this->resolveTableFields(...$properties);
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
            if ($field->isChild() || $field->isChildList()) {
                continue;
            }

            yield $field;
        }
    }

    /**
     * @return ArrayObject<int|string, TableField>
     * @throws Throwable
     */
    private function resolveTableFields(PropertyDefinition ...$properties): ArrayObject
    {
        $array = [];

        foreach ($properties as $property) {
            if ($property->isArray() || $property->isChild()) {
                $array[] = new TableField(
                    $property->getName(),
                    $property->getName(),
                    'string',
                    $property,
                    null,
                    $property->isArray(),
                    $property->isChild()
                );

                continue;
            }

            if ($property->isEntity()) {
                if ($property->isNullable()) {
                    $array[] = new TableField(
                        $property->getName(),
                        sprintf('%s_id', $property->getName()),
                        $property->getIdType(),
                        $property->withGetter(
                            sprintf(
                                '%s ? $entity->%s->getId() : null',
                                $property->getGetter(),
                                $property->getGetter(),
                            )
                        )
                    );

                    continue;
                }

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

        $reflection = new ReflectionClass($classDefinition->getName());

        if ($reflection->implementsInterface(DateTimeInterface::class)) {
            if ($voProperty->isNullable()) {
                return [
                    new TableField(
                        $voProperty->getName(),
                        $voProperty->getName(),
                        'datetime',
                        $voProperty->withGetter(
                            sprintf(
                                '%s ? $entity->%s->format(\'Y-m-d H:i:s.u\') : null',
                                $voProperty->getGetter(),
                                $voProperty->getGetter(),
                            )
                        ),
                        $voProperty->getType()
                    ),
                ];
            }

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

        $properties = array_map(
            fn (ReflectionParameter $param) => new PropertyDefinition($reflection, $param),
            $reflection->getConstructor()->getParameters()
        );

        if (count($properties) === 1) {
            $prop = current($properties);

            if ($voProperty->isNullable()) {
                return [
                    new TableField(
                        $voProperty->getName(),
                        $voProperty->getName(),
                        $prop->getType(),
                        $voProperty->withGetter(
                            sprintf(
                                '%s ? $entity->%s->%s : null',
                                $voProperty->getGetter(),
                                $voProperty->getGetter(),
                                $prop->getGetter()
                            )
                        ),
                        $voProperty->getType()
                    ),
                ];
            }

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

        return array_map(function (PropertyDefinition $prop) use ($voProperty): TableField {
            if ($voProperty->isNullable()) {
                return new TableField(
                    $voProperty->getName(),
                    sprintf('%s_%s', $voProperty->getName(), $prop->getName()),
                    $prop->getType(),
                    $voProperty->withGetter(
                        sprintf(
                            '%s ? $entity->%s->%s : null',
                            $voProperty->getGetter(),
                            $voProperty->getGetter(),
                            $prop->getGetter(),
                        )
                    ),
                    $voProperty->getType(),
                );
            }

            $classDefinition = $prop->getClassDefinition();

            if ($classDefinition instanceof ClassDefinition) {
                $class = new ReflectionClass($classDefinition->getName());

                $properties = array_map(
                    fn (ReflectionParameter $param) => new PropertyDefinition($class, $param),
                    $class->getConstructor()->getParameters()
                );

                if (count($properties) > 1) {
                    throw new Exception(sprintf('Invalid field type %s', $prop->getType()));
                }

                $vo = current($properties);

                return new TableField(
                    $voProperty->getName(),
                    sprintf('%s_%s', $voProperty->getName(), $prop->getName()),
                    $prop->getType(),
                    $voProperty->withGetter(
                        sprintf(
                            '%s->%s->%s',
                            $voProperty->getGetter(),
                            $prop->getGetter(),
                            $vo->getGetter(),
                        )
                    ),
                    $voProperty->getType(),
                );
            }

            return new TableField(
                $voProperty->getName(),
                sprintf('%s_%s', $voProperty->getName(), $prop->getName()),
                $prop->getType(),
                $voProperty->withGetter(sprintf('%s->%s', $voProperty->getGetter(), $prop->getGetter())),
                $voProperty->getType(),
            );
        }, $properties);
    }
}

<?php

declare(strict_types=1);

namespace Orm\Builder;

use Exception;
use ICanBoogie\Inflector;
use OutOfBoundsException;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionParameter;

class ClassDefinition
{
    private const CLASS_TYPE_ENTITY = 'entity';
    private const CLASS_TYPE_CHILD = 'child';
    private const CLASS_TYPE_VALUE_OBJECT = 'value_object';

    private string $name;
    private string $classType;
    private string $shortName;
    private ?string $idType;
    private ?string $childName = null;

    public function __construct(ReflectionClass $class, ?ReflectionClass $parent = null)
    {
        $this->idType = null;
        $this->name = $class->getName();
        $this->shortName = $class->getShortName();
        $this->classType = $this->findOutClassType($class, $parent);
    }

    public function isEntity(): bool
    {
        return $this->classType === self::CLASS_TYPE_ENTITY;
    }

    public function isChild(): bool
    {
        return $this->classType === self::CLASS_TYPE_CHILD;
    }

    public function isValueObject(): bool
    {
        return $this->classType === self::CLASS_TYPE_VALUE_OBJECT;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClassType(): string
    {
        return $this->classType;
    }

    public function getIdType(): ?string
    {
        return $this->idType;
    }

    public function getChildName(): ?string
    {
        return $this->childName;
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    private function findOutClassType(ReflectionClass $class, ?ReflectionClass $parent = null): string
    {
        try {
            $constructor = $class->getConstructor();
        } catch (OutOfBoundsException $e) {
            throw new Exception(
                sprintf('Unable to create ORM repository, %s::__constructor does not exist', $class->getName())
            );
        }

        /** @var ReflectionParameter[] $properties */
        $properties = $constructor->getParameters();

        foreach ($properties as $property) {
            if ($property->getName() === 'id') {
                $idType = $property->getType();

                $this->idType = $idType
                    ? (string) $idType
                    : null;

                return self::CLASS_TYPE_ENTITY;
            }
        }

        if (null !== $parent) {
            $childProp = sprintf('%s_id', strtolower($parent->getShortName()));

            foreach ($properties as $property) {
                if (Inflector::get()->underscore($property->getName()) === $childProp) {
                    $this->childName = $childProp;

                    return self::CLASS_TYPE_CHILD;
                }
            }
        }

        return self::CLASS_TYPE_VALUE_OBJECT;
    }
}

<?php

declare(strict_types=1);

namespace Orm\Builder;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionParameter;

class ClassDefinition
{
    private const CLASS_TYPE_ENTITY = 'entity';
    private const CLASS_TYPE_VALUE_OBJECT = 'value_object';

    private string $name;
    private string $classType;
    private string $shortName;
    private ?string $idType;

    public function __construct(ReflectionClass $class)
    {
        $this->idType = null;
        $this->name = $class->getName();
        $this->shortName = $class->getShortName();
        $this->classType = $this->findOutClassType($class);
    }

    public function isEntity(): bool
    {
        return $this->classType === self::CLASS_TYPE_ENTITY;
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

    public function getShortName(): string
    {
        return $this->shortName;
    }

    private function findOutClassType(ReflectionClass $class): string
    {
        /** @var ReflectionParameter[] $properties */
        $properties = $class->getConstructor()->getParameters();

        foreach ($properties as $property) {
            if ($property->getName() === 'id') {
                $idType = $property->getType();

                $this->idType = $idType
                    ? (string) $idType
                    : null;

                return self::CLASS_TYPE_ENTITY;
            }
        }

        return self::CLASS_TYPE_VALUE_OBJECT;
    }
}

<?php

declare(strict_types=1);

namespace Orm\Builder;

use Exception;
use ICanBoogie\Inflector;
use Throwable;

class TableField
{
    private const TYPES = [
        'string',
        'float',
        'int',
        'bool',
        'datetime',
    ];

    private string $objectField;
    private string $name;
    private string $type;
    private PropertyDefinition $definition;
    private ?string $valueObject;
    private bool $child;

    /**
     * @throws Throwable
     */
    public function __construct(
        string $objectField,
        string $name,
        string $type,
        PropertyDefinition $definition,
        ?string $valueObject = null,
        bool $child = false
    ) {
        if (false === in_array($type, self::TYPES)) {
            throw new Exception(sprintf('Unknown table type %s for %s', $type, $definition->getName()));
        }

        $this->objectField = $objectField;
        $this->name = Inflector::get()->underscore($name);
        $this->type = $type;
        $this->definition = $definition;
        $this->valueObject = $valueObject;
        $this->child = $child;
    }

    public function getObjectField(): string
    {
        return $this->objectField;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCast(): string
    {
        if ($this->type === 'datetime') {
            return '';
        }

        return sprintf('(%s)', $this->type);
    }

    public function isNullable(): bool
    {
        return $this->definition->isNullable();
    }

    public function getDefinition(): PropertyDefinition
    {
        return $this->definition;
    }

    public function getValueObject(): ?string
    {
        return $this->valueObject;
    }

    public function isChild(): bool
    {
        return $this->child;
    }
}

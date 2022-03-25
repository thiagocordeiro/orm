<?php

declare(strict_types=1);

namespace Orm\Builder;

use BackedEnum;
use ReflectionClass;
use ReflectionException;
use UnitEnum;

class EnumAnalyzer
{
    /**
     * @throws ReflectionException
     */
    public static function isEnum(string $class): bool
    {
        $ref = new ReflectionClass($class);

        return $ref->implementsInterface(BackedEnum::class) || $ref->implementsInterface(UnitEnum::class);
    }
}

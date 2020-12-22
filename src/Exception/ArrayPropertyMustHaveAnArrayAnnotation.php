<?php

declare(strict_types=1);

namespace Orm\Exception;

use Exception;
use Orm\Builder\ReflectionClass;
use ReflectionParameter;

class ArrayPropertyMustHaveAnArrayAnnotation extends Exception
{
    public function __construct(ReflectionParameter $param, ReflectionClass $class, string $type)
    {
        parent::__construct(
            sprintf(
                'Array property %s::%s must have an array annotation, use %s[] instead',
                $class->getName(),
                $param->getName(),
                $type
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace Orm\Exception;

use Exception;
use Orm\Builder\ReflectionClass;
use ReflectionParameter;

class ArrayPropertyMustHaveATypeAnnotation extends Exception
{
    public function __construct(ReflectionParameter $param, ReflectionClass $class)
    {
        parent::__construct(
            sprintf(
                'Array property %s::%s must have an array annotation',
                $class->getName(),
                $param->getName()
            )
        );
    }
}

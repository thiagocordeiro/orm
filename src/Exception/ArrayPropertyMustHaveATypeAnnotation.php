<?php

declare(strict_types=1);

namespace Orm\Exception;

use Exception;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionParameter;

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

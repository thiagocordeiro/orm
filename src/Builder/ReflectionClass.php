<?php

declare(strict_types=1);

namespace Orm\Builder;

use Orm\Exception\ClassMustHaveAConstructor;
use ReflectionClass as PHPReflectionClass;
use ReflectionMethod;

class ReflectionClass extends PHPReflectionClass
{
    public function __construct(string $objectOrClass)
    {
        parent::__construct($objectOrClass);
    }

    public function getConstructor(): ReflectionMethod
    {
        $constructor = parent::getConstructor();

        if (null === $constructor) {
            throw new ClassMustHaveAConstructor($this->getName());
        }

        return $constructor;
    }
}

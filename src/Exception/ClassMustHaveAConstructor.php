<?php

declare(strict_types=1);

namespace Orm\Exception;

use Exception;

class ClassMustHaveAConstructor extends Exception
{
    public function __construct(string $class)
    {
        parent::__construct(
            sprintf('Class %s must have a constructor', $class)
        );
    }
}

<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Vo;

use InvalidArgumentException;

class Age
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 18 || $value > 90) {
            throw new InvalidArgumentException('Age out of range');
        }

        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}

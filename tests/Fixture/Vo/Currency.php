<?php

namespace Test\Orm\Fixture\Vo;

class Currency
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function EUR(): self
    {
        return new self('EUR');
    }
}

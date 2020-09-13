<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Vo;

use InvalidArgumentException;

class Email
{
    private string $email;

    public function __construct(string $email)
    {
        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }

        $this->email = $email;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}

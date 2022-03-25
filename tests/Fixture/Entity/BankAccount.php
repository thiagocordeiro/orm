<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

use Test\Orm\Fixture\Enum\AccountType;

class BankAccount
{
    private string $id;
    private string $number;
    private AccountType $type;

    public function __construct(string $id, string $number, AccountType $type)
    {
        $this->id = $id;
        $this->number = $number;
        $this->type = $type;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getType(): AccountType
    {
        return $this->type;
    }
}

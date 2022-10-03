<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

class AccountHolder
{
    private string $id;
    private string $emailAddress;
    private bool $enabled;

    public function __construct(string $id, string $emailAddress, bool $enabled)
    {

        $this->id = $id;
        $this->emailAddress = $emailAddress;
        $this->enabled = $enabled;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}

<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Entity;

use DateTimeImmutable;

class Post
{
    private string $id;
    private string $title;
    private DateTimeImmutable $createdAt;

    public function __construct(string $id, string $title, DateTimeImmutable $createdAt)
    {
        $this->id = $id;
        $this->title = $title;
        $this->createdAt = $createdAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

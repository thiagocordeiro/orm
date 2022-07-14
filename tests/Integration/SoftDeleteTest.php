<?php

declare(strict_types=1);

namespace Test\Orm\Integration;

use DateTimeImmutable;
use Test\Orm\Config\IntegrationTestCase;
use Test\Orm\Fixture\Entity\Post;

class SoftDeleteTest extends IntegrationTestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = new DateTimeImmutable('2020-09-14 22:25:30');
    }

    public function testWhenEntityWasSoftDeletedThenLoadByIdWillReturnNotNull(): void
    {
        $repository = $this->em->getRepository(Post::class);
        $post = new Post('post-xxx', 'Post Title', $this->now);
        $repository->insert($post);

        $repository->delete($post);

        $this->assertNotNull($repository->loadById('post-xxx'));
    }

    public function testWhenEntityWasSoftDeletedThenLoadByQueryWillReturnPostWithDeletionDate(): void
    {
        $repository = $this->em->getRepository(Post::class);
        $post = new Post('post-xxx', 'Post Title', $this->now);
        $repository->insert($post);

        $repository->delete($post);

        $this->assertEquals(
            $post,
            $repository->loadByQuery(
                'SELECT * FROM posts WHERE id = :id AND deleted_at IS NOT NULL',
                ['id' => 'post-xxx'],
            ),
        );
    }

    public function testWhenEntityWasSoftDeletedThenSelectOneWillReturnPostWithDeletionDate(): void
    {
        $repository = $this->em->getRepository(Post::class);
        $post = new Post('post-xxx', 'Post Title', $this->now);
        $repository->insert($post);

        $repository->delete($post);

        $this->assertEquals($post, $repository->selectOne(['id' => 'post-xxx']));
    }
}

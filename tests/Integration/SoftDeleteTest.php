<?php

declare(strict_types=1);

namespace Test\Orm\Integration;

use DateTimeImmutable;
use Orm\Repository;
use Test\Orm\Config\IntegrationTestCase;
use Test\Orm\Fixture\Entity\Post;

class SoftDeleteTest extends IntegrationTestCase
{
    private DateTimeImmutable $now;
    private Repository $repository;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = new DateTimeImmutable('2020-09-14 22:25:30');
    }

    public function testWhenEntityWasSoftDeletedThenLoadByIdWillReturnNotNull(): void
    {
        $this->insertPost('post-xxx');

        $this->repository->delete($this->post);

        $this->assertNotNull($this->repository->loadById('post-xxx'));
    }

    public function testWhenEntityWasSoftDeletedThenLoadByQueryWillReturnPostWithDeletionDate(): void
    {
        $this->insertPost('post-xxx');

        $this->repository->delete($this->post);

        $this->assertEquals(
            $this->post,
            $this->repository->loadByQuery(
                'SELECT * FROM posts WHERE id = :id AND deleted_at IS NOT NULL',
                ['id' => 'post-xxx'],
            ),
        );
    }

    public function testWhenEntityWasSoftDeletedThenSelectWillReturnNull(): void
    {
        $this->insertPost('post-xxx');

        $this->repository->delete($this->post);

        $this->assertEmpty(iterator_to_array($this->repository->select(['id' => 'post-xxx'])));
    }

    public function testWhenEntityWasSoftDeletedThenSelectByQueryWillReturnPostWithDeletionDate(): void
    {
        $this->insertPost('post-xxx');

        $this->repository->delete($this->post);

        $this->assertEquals(
            [$this->post],
            iterator_to_array(
                $this->repository->selectByQuery(
                    'SELECT * FROM posts WHERE id = :id AND deleted_at IS NOT NULL',
                    ['id' => 'post-xxx'],
                ),
            ),
        );
    }

    public function testWhenEntityWasSoftDeletedThenSelectOneWillReturnPostWithDeletionDate(): void
    {
        $this->insertPost('post-xxx');

        $this->repository->delete($this->post);

        $this->assertEquals($this->post, $this->repository->selectOne(['id' => 'post-xxx']));
    }

    private function insertPost(string $postId): void
    {
        $this->repository = $this->em->getRepository(Post::class);
        $this->post = new Post($postId, 'Post Title', $this->now);
        $this->repository->insert($this->post);
    }
}

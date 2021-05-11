<?php

declare(strict_types=1);

namespace Orm;

interface RepositoryFactory
{
    public function __invoke(Connection $connection, EntityManager $em): Repository;
}

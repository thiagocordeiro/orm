<?php

declare(strict_types=1);

namespace Test\Orm\Integration;

use Test\Orm\Config\IntegrationTestCase;
use Test\Orm\Fixture\Entity\NullableProperty;
use Test\Orm\Fixture\Vo\Height;
use Throwable;

class NullablePropertyTest extends IntegrationTestCase
{
    /**
     * @throws Throwable
     */
    public function testSaveAndRetrieveWithNullValues(): void
    {
        $repository = $this->em->getRepository(NullableProperty::class);
        $entity = new NullableProperty('nnn', null, null, null);

        $repository->insert($entity);

        $this->assertEquals(
            new NullableProperty('nnn', null, null, null),
            $repository->loadById('nnn')
        );
    }

    /**
     * @throws Throwable
     */
    public function testSaveAndRetrieveWithSomeNullValues(): void
    {
        $repository = $this->em->getRepository(NullableProperty::class);
        $entity = new NullableProperty('nnn', null, new Height(1.76), null);

        $repository->insert($entity);

        $this->assertEquals(
            new NullableProperty('nnn', null, new Height(1.76), null),
            $repository->loadById('nnn')
        );
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\Persister\Entity\AbstractEntityPersister;
use Doctrine\ORM\Cache\Persister\Entity\ReadOnlyCachedEntityPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\Tests\Models\Cache\Country;

/**
 * @group DDC-2183
 */
class ReadOnlyCachedEntityPersisterTest extends AbstractEntityPersisterTest
{
    protected function createPersister(EntityManager $em, EntityPersister $persister, Region $region, ClassMetadata $metadata): AbstractEntityPersister
    {
        return new ReadOnlyCachedEntityPersister($persister, $region, $em, $metadata);
    }

    public function testInvokeUpdate(): void
    {
        $this->expectException('Doctrine\ORM\Cache\CacheException');
        $this->expectExceptionMessage('Cannot update a readonly entity "Doctrine\Tests\Models\Cache\Country"');
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $persister->update($entity);
    }
}

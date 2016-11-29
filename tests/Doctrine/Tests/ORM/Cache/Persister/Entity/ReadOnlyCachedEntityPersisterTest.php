<?php

namespace Doctrine\Tests\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\Cache\Persister\Entity\ReadOnlyCachedEntityPersister;

/**
 * @group DDC-2183
 */
class ReadOnlyCachedEntityPersisterTest extends AbstractEntityPersisterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createPersister(EntityManager $em, EntityPersister $persister, Region $region, ClassMetadata $metadata)
    {
        return new ReadOnlyCachedEntityPersister($persister, $region, $em, $metadata);
    }

    /**
     * @expectedException Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Cannot update a readonly entity "Doctrine\Tests\Models\Cache\Country"
     */
    public function testInvokeUpdate()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $persister->update($entity);
    }
}

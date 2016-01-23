<?php

namespace Shitty\Tests\ORM\Cache\Persister\Entity;

use Shitty\ORM\Cache\Region;
use Shitty\ORM\EntityManager;
use Shitty\Tests\Models\Cache\Country;
use Shitty\ORM\Mapping\ClassMetadata;
use Shitty\ORM\Persisters\Entity\EntityPersister;
use Shitty\ORM\Cache\Persister\Entity\ReadOnlyCachedEntityPersister;

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

<?php

namespace Doctrine\Tests\ORM\Cache\Persister;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\EntityPersister;
use Doctrine\ORM\Cache\Persister\ReadWriteCachedEntityPersister;

/**
 * @group DDC-2183
 */
class ReadWriteCachedEntityPersisterTest extends AbstractEntityPersisterTest
{
    protected $regionMockMethods = array(
        'getName',
        'contains',
        'get',
        'put',
        'evict',
        'evictAll',
        'readLock',
        'readUnlock',
    );

    /**
     * {@inheritdoc}
     */
    protected function createPersister(EntityManager $em, EntityPersister $persister, Region $region, ClassMetadata $metadata)
    {
        return new ReadWriteCachedEntityPersister($persister, $region, $em, $metadata);
    }

    /**
     * @return \Doctrine\ORM\Cache\Region
     */
    protected function createRegion()
    {
        return $this->getMock('Doctrine\ORM\Cache\ConcurrentRegion', $this->regionMockMethods);
    }
}
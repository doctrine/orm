<?php

namespace Doctrine\Tests\ORM\Cache\Persister;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Persisters\CollectionPersister;
use Doctrine\ORM\Cache\Persister\ReadWriteCachedCollectionPersister;

/**
 * @group DDC-2183
 */
class ReadWriteCachedCollectionPersisterTest extends AbstractCollectionPersisterTest
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
    protected function createPersister(EntityManager $em, CollectionPersister $persister, Region $region, array $mapping)
    {
        return new ReadWriteCachedCollectionPersister($persister, $region, $em, $mapping);
    }

    /**
     * @return \Doctrine\ORM\Cache\Region
     */
    protected function createRegion()
    {
        return $this->getMock('Doctrine\ORM\Cache\ConcurrentRegion', $this->regionMockMethods);
    }
}
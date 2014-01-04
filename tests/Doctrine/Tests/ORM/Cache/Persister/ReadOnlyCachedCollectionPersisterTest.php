<?php

namespace Doctrine\Tests\ORM\Cache\Persister;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Persisters\CollectionPersister;
use Doctrine\ORM\Cache\Persister\ReadOnlyCachedCollectionPersister;

/**
 * @group DDC-2183
 */
class ReadOnlyCachedCollectionPersisterTest extends AbstractCollectionPersisterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createPersister(EntityManager $em, CollectionPersister $persister, Region $region, array $mapping)
    {
        return new ReadOnlyCachedCollectionPersister($persister, $region, $em, $mapping);
    }
}
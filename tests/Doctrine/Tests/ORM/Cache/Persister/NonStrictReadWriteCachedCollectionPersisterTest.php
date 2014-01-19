<?php

namespace Doctrine\Tests\ORM\Cache\Persister;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Persisters\CollectionPersister;
use Doctrine\ORM\Cache\Persister\NonStrictReadWriteCachedCollectionPersister;

/**
 * @group DDC-2183
 */
class NonStrictReadWriteCachedCollectionPersisterTest extends AbstractCollectionPersisterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createPersister(EntityManager $em, CollectionPersister $persister, Region $region, array $mapping)
    {
        return new NonStrictReadWriteCachedCollectionPersister($persister, $region, $em, $mapping);
    }

}
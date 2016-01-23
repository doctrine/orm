<?php

namespace Shitty\Tests\ORM\Cache\Persister\Collection;

use Shitty\ORM\Cache\Region;
use Shitty\ORM\EntityManager;
use Shitty\ORM\Persisters\Collection\CollectionPersister;
use Shitty\ORM\Cache\Persister\Collection\ReadOnlyCachedCollectionPersister;

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

<?php

namespace Doctrine\Tests\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\NonStrictReadWriteCachedCollectionPersister;

/**
 * @group DDC-2183
 */
class NonStrictReadWriteCachedCollectionPersisterTest extends AbstractCollectionPersisterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createPersister(
        EntityManager $em,
        CollectionPersister $persister,
        Region $region,
        AssociationMetadata $association
    )
    {
        return new NonStrictReadWriteCachedCollectionPersister($persister, $region, $em, $association);
    }

}

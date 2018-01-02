<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
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
        EntityManagerInterface $em,
        CollectionPersister $persister,
        Region $region,
        AssociationMetadata $association
    )
    {
        return new NonStrictReadWriteCachedCollectionPersister($persister, $region, $em, $association);
    }

}

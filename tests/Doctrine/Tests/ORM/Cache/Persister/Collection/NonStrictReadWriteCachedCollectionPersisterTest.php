<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\Persister\Collection\AbstractCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\NonStrictReadWriteCachedCollectionPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;

/**
 * @group DDC-2183
 */
class NonStrictReadWriteCachedCollectionPersisterTest extends AbstractCollectionPersisterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createPersister(EntityManager $em, CollectionPersister $persister, Region $region, array $mapping): AbstractCollectionPersister
    {
        return new NonStrictReadWriteCachedCollectionPersister($persister, $region, $em, $mapping);
    }
}

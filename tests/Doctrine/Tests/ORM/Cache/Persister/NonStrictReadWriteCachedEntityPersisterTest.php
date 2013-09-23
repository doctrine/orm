<?php

namespace Doctrine\Tests\ORM\Cache\Persister;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\EntityPersister;
use Doctrine\ORM\Cache\Persister\NonStrictReadWriteCachedEntityPersister;

/**
 * @group DDC-2183
 */
class NonStrictReadWriteCachedEntityPersisterTest extends AbstractEntityPersisterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createPersister(EntityManager $em, EntityPersister $persister, Region $region, ClassMetadata $metadata)
    {
        return new NonStrictReadWriteCachedEntityPersister($persister, $region, $em, $metadata);
    }

}
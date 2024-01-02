<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\Persister\Collection\AbstractCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\ReadOnlyCachedCollectionPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;

/** @group DDC-2183 */
class ReadOnlyCachedCollectionPersisterTest extends CollectionPersisterTestCase
{
    protected function createPersister(EntityManagerInterface $em, CollectionPersister $persister, Region $region, array $mapping): AbstractCollectionPersister
    {
        return new ReadOnlyCachedCollectionPersister($persister, $region, $em, $mapping);
    }
}

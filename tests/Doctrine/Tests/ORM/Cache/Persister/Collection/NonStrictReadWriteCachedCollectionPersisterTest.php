<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\Persister\Collection\AbstractCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\NonStrictReadWriteCachedCollectionPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2183')]
class NonStrictReadWriteCachedCollectionPersisterTest extends CollectionPersisterTestCase
{
    protected function createPersister(
        EntityManagerInterface $em,
        CollectionPersister $persister,
        Region $region,
        AssociationMapping $mapping,
    ): AbstractCollectionPersister {
        return new NonStrictReadWriteCachedCollectionPersister($persister, $region, $em, $mapping);
    }
}

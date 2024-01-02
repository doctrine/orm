<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache;
use Doctrine\ORM\Cache\Persister\Collection\CachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;

/**
 * Contract for building second level cache regions components.
 */
interface CacheFactory
{
    /**
     * Build an entity persister for the given entity metadata.
     */
    public function buildCachedEntityPersister(EntityManagerInterface $em, EntityPersister $persister, ClassMetadata $metadata): CachedEntityPersister;

    /** Build a collection persister for the given relation mapping. */
    public function buildCachedCollectionPersister(
        EntityManagerInterface $em,
        CollectionPersister $persister,
        AssociationMapping $mapping,
    ): CachedCollectionPersister;

    /**
     * Build a query cache based on the given region name
     */
    public function buildQueryCache(EntityManagerInterface $em, string|null $regionName = null): QueryCache;

    /**
     * Build an entity hydrator
     */
    public function buildEntityHydrator(EntityManagerInterface $em, ClassMetadata $metadata): EntityHydrator;

    /**
     * Build a collection hydrator
     */
    public function buildCollectionHydrator(EntityManagerInterface $em, AssociationMapping $mapping): CollectionHydrator;

    /**
     * Build a cache region
     *
     * @param array<string,mixed> $cache The cache configuration.
     */
    public function getRegion(array $cache): Region;

    /**
     * Build timestamp cache region
     */
    public function getTimestampRegion(): TimestampRegion;

    /**
     * Build \Doctrine\ORM\Cache
     */
    public function createCache(EntityManagerInterface $entityManager): Cache;
}

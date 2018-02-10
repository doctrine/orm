<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache;
use Doctrine\ORM\Cache\Persister\Collection\CachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\CacheMetadata;
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
     *
     * @param EntityManagerInterface $em        The entity manager.
     * @param EntityPersister        $persister The entity persister that will be cached.
     * @param ClassMetadata          $metadata  The entity metadata.
     *
     * @return CachedEntityPersister
     */
    public function buildCachedEntityPersister(
        EntityManagerInterface $em,
        EntityPersister $persister,
        ClassMetadata $metadata
    );

    /**
     * Build a collection persister for the given relation mapping.
     *
     * @param EntityManagerInterface $em          The entity manager.
     * @param CollectionPersister    $persister   The collection persister that will be cached.
     * @param AssociationMetadata    $association The association mapping.
     *
     * @return CachedCollectionPersister
     */
    public function buildCachedCollectionPersister(
        EntityManagerInterface $em,
        CollectionPersister $persister,
        AssociationMetadata $association
    );

    /**
     * Build a query cache based on the given region name
     *
     * @param EntityManagerInterface $em         The Entity manager.
     * @param string                 $regionName The region name.
     *
     * @return QueryCache The built query cache.
     */
    public function buildQueryCache(EntityManagerInterface $em, $regionName = null);

    /**
     * Build an entity hydrator
     *
     * @param EntityManagerInterface $em       The Entity manager.
     * @param ClassMetadata          $metadata The entity metadata.
     *
     * @return EntityHydrator The built entity hydrator.
     */
    public function buildEntityHydrator(EntityManagerInterface $em, ClassMetadata $metadata);

    /**
     * Build a collection hydrator
     *
     * @param EntityManagerInterface $em          The Entity manager.
     * @param AssociationMetadata    $association The association mapping.
     *
     * @return CollectionHydrator The built collection hydrator.
     */
    public function buildCollectionHydrator(EntityManagerInterface $em, AssociationMetadata $association);

    /**
     * Build a cache region
     *
     * @param CacheMetadata $cache The cache configuration.
     *
     * @return Region The cache region.
     */
    public function getRegion(CacheMetadata $cache);

    /**
     * Build timestamp cache region
     *
     * @return TimestampRegion The timestamp region.
     */
    public function getTimestampRegion();

    /**
     * Build \Doctrine\ORM\Cache
     *
     * @return Cache
     */
    public function createCache(EntityManagerInterface $entityManager);
}

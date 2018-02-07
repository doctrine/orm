<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

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
     * @param \Doctrine\ORM\EntityManagerInterface            $em        The entity manager.
     * @param \Doctrine\ORM\Persisters\Entity\EntityPersister $persister The entity persister that will be cached.
     * @param \Doctrine\ORM\Mapping\ClassMetadata             $metadata  The entity metadata.
     *
     * @return \Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister
     */
    public function buildCachedEntityPersister(
        EntityManagerInterface $em,
        EntityPersister $persister,
        ClassMetadata $metadata
    );

    /**
     * Build a collection persister for the given relation mapping.
     *
     * @param \Doctrine\ORM\EntityManagerInterface                    $em          The entity manager.
     * @param \Doctrine\ORM\Persisters\Collection\CollectionPersister $persister   The collection persister that will be cached.
     * @param \Doctrine\ORM\Mapping\AssociationMetadata               $association The association mapping.
     *
     * @return \Doctrine\ORM\Cache\Persister\Collection\CachedCollectionPersister
     */
    public function buildCachedCollectionPersister(
        EntityManagerInterface $em,
        CollectionPersister $persister,
        AssociationMetadata $association
    );

    /**
     * Build a query cache based on the given region name
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em         The Entity manager.
     * @param string                               $regionName The region name.
     *
     * @return \Doctrine\ORM\Cache\QueryCache The built query cache.
     */
    public function buildQueryCache(EntityManagerInterface $em, $regionName = null);

    /**
     * Build an entity hydrator
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em       The Entity manager.
     * @param \Doctrine\ORM\Mapping\ClassMetadata  $metadata The entity metadata.
     *
     * @return \Doctrine\ORM\Cache\EntityHydrator The built entity hydrator.
     */
    public function buildEntityHydrator(EntityManagerInterface $em, ClassMetadata $metadata);

    /**
     * Build a collection hydrator
     *
     * @param \Doctrine\ORM\EntityManagerInterface      $em          The Entity manager.
     * @param \Doctrine\ORM\Mapping\AssociationMetadata $association The association mapping.
     *
     * @return \Doctrine\ORM\Cache\CollectionHydrator The built collection hydrator.
     */
    public function buildCollectionHydrator(EntityManagerInterface $em, AssociationMetadata $association);

    /**
     * Build a cache region
     *
     * @param \Doctrine\ORM\Mapping\CacheMetadata $cache The cache configuration.
     *
     * @return \Doctrine\ORM\Cache\Region The cache region.
     */
    public function getRegion(CacheMetadata $cache);

    /**
     * Build timestamp cache region
     *
     * @return \Doctrine\ORM\Cache\TimestampRegion The timestamp region.
     */
    public function getTimestampRegion();

    /**
     * Build \Doctrine\ORM\Cache
     *
     * @return \Doctrine\ORM\Cache
     */
    public function createCache(EntityManagerInterface $entityManager);
}

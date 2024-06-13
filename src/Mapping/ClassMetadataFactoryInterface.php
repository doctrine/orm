<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Psr\Cache\CacheItemPoolInterface;

/** @template-extends ClassMetadataFactory<ClassMetadata> */
interface ClassMetadataFactoryInterface extends ClassMetadataFactory
{
    /**
     * Sets the cache for created metadata.
     */
    public function setCache(CacheItemPoolInterface $cache): void;

    /**
     * Sets the entity manager owning the factory.
     */
    public function setEntityManager(EntityManagerInterface $em): void;

    /**
     * @param A $maybeOwningSide
     *
     * @return (A is ManyToManyAssociationMapping ? ManyToManyOwningSideMapping : (
     *     A is OneToOneAssociationMapping ? OneToOneOwningSideMapping : (
     *     A is OneToManyAssociationMapping ? ManyToOneAssociationMapping : (
     *     A is ManyToOneAssociationMapping ? ManyToOneAssociationMapping :
     *     ManyToManyOwningSideMapping|OneToOneOwningSideMapping|ManyToOneAssociationMapping
     * ))))
     *
     * @template A of AssociationMapping
     */
    public function getOwningSide(AssociationMapping $maybeOwningSide): OwningSideMapping;
}

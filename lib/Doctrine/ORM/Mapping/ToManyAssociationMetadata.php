<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;

/**
 * Class ToManyAssociationMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ToManyAssociationMetadata extends AssociationMetadata
{
    /** @var array<string, string> */
    private $orderBy = [];

    /** @var null|string */
    private $indexedBy;

    /**
     * @param array $orderBy
     */
    public function setOrderBy(array $orderBy) : void
    {
        $this->orderBy = $orderBy;
    }

    /**
     * @return array
     */
    public function getOrderBy() : array
    {
        return $this->orderBy;
    }

    /**
     * @param null|string $indexedBy
     */
    public function setIndexedBy(string $indexedBy = null) : void
    {
        $this->indexedBy = $indexedBy;
    }

    /**
     * @return null|string
     */
    public function getIndexedBy() : ?string
    {
        return $this->indexedBy;
    }

    /**
     * @param object                 $owner
     * @param null|array|Collection  $collection
     * @param EntityManagerInterface $entityManager
     *
     * @return PersistentCollection
     */
    public function wrap($owner, $collection, EntityManagerInterface $entityManager) : PersistentCollection
    {
        if ($collection instanceof PersistentCollection) {
            if ($collection->getOwner() === $owner) {
                return $collection;
            }

            $collection = $collection->getValues();
        }

        // If $value is not a Collection then use an ArrayCollection.
        if (! $collection instanceof Collection) {
            // @todo guilhermeblanco Conceptually, support to custom collections by replacing ArrayCollection creation.
            $collection = new ArrayCollection((array) $collection);
        }

        // Inject PersistentCollection
        $targetClass = $entityManager->getClassMetadata($this->getTargetEntity());
        $collection  = new PersistentCollection($entityManager, $targetClass, $collection);

        $collection->setOwner($owner, $this);
        $collection->setDirty( ! $collection->isEmpty());
        $collection->setInitialized(true);

        return $collection;
    }
}

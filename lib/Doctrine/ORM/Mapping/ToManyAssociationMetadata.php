<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;

class ToManyAssociationMetadata extends AssociationMetadata
{
    /** @var string[] */
    private $orderBy = [];

    /** @var string|null */
    private $indexedBy;

    /**
     * @param mixed[] $orderBy
     */
    public function setOrderBy(array $orderBy) : void
    {
        $this->orderBy = $orderBy;
    }

    /**
     * @return mixed[]
     */
    public function getOrderBy() : array
    {
        return $this->orderBy;
    }

    public function setIndexedBy(?string $indexedBy = null) : void
    {
        $this->indexedBy = $indexedBy;
    }

    public function getIndexedBy() : ?string
    {
        return $this->indexedBy;
    }

    /**
     * @param object                         $owner
     * @param Collection|array|object[]|null $collection
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
        $collection->setDirty(! $collection->isEmpty());
        $collection->setInitialized(true);

        return $collection;
    }
}

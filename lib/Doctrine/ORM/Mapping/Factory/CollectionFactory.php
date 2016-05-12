<?php

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\PersistentCollection;

class CollectionFactory
{
    /**
     * This method creates a custom collection for the entity defined by ClassMetadata $class.
     * If no custom collection is specified, it will fallback to PersistentCollection.
     * The custom collection must extend from PersistentCollection.
     *
     * @param EntityManagerInterface $em
     * @param ClassMetadata          $class
     * @param Collection             $collection the contents of the specific collection to create
     *
     * @return PersistentCollection
     */
    public function create(EntityManagerInterface $em, ClassMetadata $class, Collection $collection)
    {
        $customCollectionClassName = $class->customCollectionClassName;

        if (empty($customCollectionClassName)) {
            $customCollectionClassName = PersistentCollection::class;
        } elseif (! is_a($customCollectionClassName, PersistentCollection::class, true)) {
            throw new ORMInvalidArgumentException(
                'The custom collection specified for entity ' . $class->getName()
                . ' (' . $customCollectionClassName . ') must extend ' . PersistentCollection::class . '.'
            );
        }

        return new $customCollectionClassName($em, $class, $collection);
    }

    /**
     * This method creates the default collection to be used in the UnitOfWork.
     *
     * @param EntityManagerInterface $em
     * @param ClassMetadata          $class
     * @param Collection             $collection the contents of the specific collection to create
     *
     * @return PersistentCollection
     */
    public function createDefault(EntityManagerInterface $em, ClassMetadata $class, Collection $collection)
    {
        return new PersistentCollection($em, $class, $collection);
    }
}

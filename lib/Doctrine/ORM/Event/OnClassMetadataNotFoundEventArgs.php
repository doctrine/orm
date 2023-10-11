<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;

/**
 * Class that holds event arguments for a `onClassMetadataNotFound` event.
 *
 * This object is mutable by design, allowing callbacks having access to it to set the
 * found metadata in it, and therefore "cancelling" a `onClassMetadataNotFound` event
 *
 * @extends ManagerEventArgs<EntityManagerInterface>
 */
class OnClassMetadataNotFoundEventArgs extends ManagerEventArgs
{
    private ClassMetadata|null $foundMetadata = null;

    /** @param EntityManagerInterface $objectManager */
    public function __construct(
        private readonly string $className,
        ObjectManager $objectManager,
    ) {
        parent::__construct($objectManager);
    }

    public function setFoundMetadata(ClassMetadata|null $classMetadata): void
    {
        $this->foundMetadata = $classMetadata;
    }

    public function getFoundMetadata(): ClassMetadata|null
    {
        return $this->foundMetadata;
    }

    /**
     * Retrieve class name for which a failed metadata fetch attempt was executed
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}

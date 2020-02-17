<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;

/**
 * Class that holds event arguments for a `onClassMetadataNotFound` event.
 *
 * This object is mutable by design, allowing callbacks having access to it to set the
 * found metadata in it, and therefore "cancelling" a `onClassMetadataNotFound` event
 */
class OnClassMetadataNotFoundEventArgs extends ManagerEventArgs
{
    /** @var string */
    private $className;

    /** @var ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var ClassMetadata|null */
    private $foundMetadata;

    public function __construct(
        string $className,
        ClassMetadataBuildingContext $metadataBuildingContext,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($entityManager);

        $this->className               = $className;
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function setFoundMetadata(?ClassMetadata $classMetadata) : void
    {
        $this->foundMetadata = $classMetadata;
    }

    public function getFoundMetadata() : ?ClassMetadata
    {
        return $this->foundMetadata;
    }

    /**
     * Retrieve class name for which a failed metadata fetch attempt was executed
     */
    public function getClassName() : string
    {
        return $this->className;
    }

    public function getClassMetadataBuildingContext() : ClassMetadataBuildingContext
    {
        return $this->metadataBuildingContext;
    }
}

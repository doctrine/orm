<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class that holds event arguments for a `onClassMetadataNotFound` event.
 *
 * This object is mutable by design, allowing callbacks having access to it to set the
 * found metadata in it, and therefore "cancelling" a `onClassMetadataNotFound` event
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @since  2.5
 */
class OnClassMetadataNotFoundEventArgs extends ManagerEventArgs
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var ClassMetadata|null
     */
    private $foundMetadata;

    /**
     * Constructor.
     *
     * @param string        $className
     * @param ObjectManager $objectManager
     */
    public function __construct($className, ObjectManager $objectManager)
    {
        $this->className = (string) $className;

        parent::__construct($objectManager);
    }

    /**
     * @param ClassMetadata|null $classMetadata
     */
    public function setFoundMetadata(ClassMetadata $classMetadata = null)
    {
        $this->foundMetadata = $classMetadata;
    }

    /**
     * @return ClassMetadata|null
     */
    public function getFoundMetadata()
    {
        return $this->foundMetadata;
    }

    /**
     * Retrieve class name for which a failed metadata fetch attempt was executed
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}


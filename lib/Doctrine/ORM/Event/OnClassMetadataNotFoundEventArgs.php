<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;

use function func_num_args;

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
    /** @var string */
    private $className;

    /** @var ClassMetadata|null */
    private $foundMetadata;

    /**
     * @param string                 $className
     * @param EntityManagerInterface $objectManager
     */
    public function __construct($className, ObjectManager $objectManager)
    {
        $this->className = (string) $className;

        parent::__construct($objectManager);
    }

    /**
     * @return void
     */
    public function setFoundMetadata(?ClassMetadata $classMetadata = null)
    {
        if (func_num_args() < 1) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/9791',
                'Calling %s without arguments is deprecated, pass null instead.',
                __METHOD__
            );
        }

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

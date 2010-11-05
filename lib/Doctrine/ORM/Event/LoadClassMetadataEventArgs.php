<?php

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;

/**
 * Class that holds event arguments for a loadMetadata event.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @since 2.0
 */
class LoadClassMetadataEventArgs extends EventArgs
{
    /**
     * @var ClassMetadata
     */
    private $classMetadata;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param ClassMetadata $classMetadata
     * @param EntityManager $em
     */
    public function __construct(ClassMetadata $classMetadata, EntityManager $em)
    {
        $this->classMetadata = $classMetadata;
        $this->em = $em;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
}


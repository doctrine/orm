<?php

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
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
     * @param ClassMetadataInfo $classMetadata
     * @param EntityManager $em
     */
    public function __construct(ClassMetadataInfo $classMetadata, EntityManager $em)
    {
        $this->classMetadata = $classMetadata;
        $this->em = $em;
    }

    /**
     * @return ClassMetadataInfo
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


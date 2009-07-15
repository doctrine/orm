<?php

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;

/**
 * Class that holds event arguments for a loadMetadata event.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @since 2.0
 */
class LoadClassMetadataEventArgs extends EventArgs
{
    private $_classMetadata;

    public function __construct(\Doctrine\ORM\Mapping\ClassMetadata $classMetadata)
    {
        $this->_classMetadata = $classMetadata;
    }

    public function getClassMetadata()
    {
        return $this->_classMetadata;
    }
}


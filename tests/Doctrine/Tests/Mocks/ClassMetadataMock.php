<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Mock class for ClassMetadata.
 */
class ClassMetadataMock extends ClassMetadata
{
    /* Mock API */

    /**
     * {@inheritdoc}
     */
    public function setIdGeneratorType($type)
    {
        $this->_generatorType = $type;
    }
}

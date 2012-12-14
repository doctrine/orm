<?php

namespace Doctrine\Tests\Mocks;

/**
 * Mock class for ClassMetadata.
 */
class ClassMetadataMock extends \Doctrine\ORM\Mapping\ClassMetadata
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

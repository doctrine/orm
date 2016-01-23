<?php

namespace Shitty\Tests\Mocks;

/**
 * Mock class for ClassMetadata.
 */
class ClassMetadataMock extends \Shitty\ORM\Mapping\ClassMetadata
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

<?php

namespace Doctrine\Tests\Mocks;

class ClassMetadataMock extends \Doctrine\ORM\Mapping\ClassMetadata
{
    /* Mock API */
    
    public function setIdGeneratorType($type)
    {
        $this->_generatorType = $type;
    }
    
}
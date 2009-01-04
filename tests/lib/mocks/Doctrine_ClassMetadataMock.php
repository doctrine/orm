<?php

#namespace Doctrine\Tests\Mocks;

class Doctrine_ClassMetadataMock extends Doctrine_ORM_Mapping_ClassMetadata
{
    /* Mock API */
    
    public function setIdGeneratorType($type) {
        $this->_generatorType = $type;
    }
    
}


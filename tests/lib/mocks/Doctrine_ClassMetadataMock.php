<?php

class Doctrine_ClassMetadataMock extends Doctrine_ORM_Mapping_ClassMetadata
{
    
    
    /* Mock API */
    
    public function setIdGenerator(Doctrine_ORM_Id_AbstractIdGenerator $g) {
        $this->_idGenerator = $g;
    }
    
}

?>
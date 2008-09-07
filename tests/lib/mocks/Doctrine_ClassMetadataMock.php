<?php

class Doctrine_ClassMetadataMock extends Doctrine_ClassMetadata
{
    
    
    /* Mock API */
    
    public function setIdGenerator(Doctrine_Id_AbstractIdGenerator $g) {
        $this->_idGenerator = $g;
    }
    
}

?>
<?php

/**
 * Id generator that uses a single-row database table and a hi/lo algorithm.  
 *
 * @since 2.0
 */
class Doctrine_Id_TableGenerator extends Doctrine_Id_AbstractIdGenerator
{
    
    public function generate(Doctrine_Entity $entity)
    {
        throw new Exception("Not implemented");
    }
    
}

?>
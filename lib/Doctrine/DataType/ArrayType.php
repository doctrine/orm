<?php

/**
 * Type that maps PHP arrays to VARCHAR SQL type.
 *
 * @since 2.0
 */
class Doctrine_DataType_ArrayType extends Doctrine_DataType
{
    
    
    public function getName()
    {
        return 'array';
    }
}

?>
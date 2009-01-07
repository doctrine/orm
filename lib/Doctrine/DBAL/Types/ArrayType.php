<?php

/**
 * Type that maps PHP arrays to VARCHAR SQL type.
 *
 * @since 2.0
 */
class Doctrine_DBAL_Types_ArrayType extends Doctrine_DBAL_Types_Type
{
    
    
    public function getName()
    {
        return 'Array';
    }
}


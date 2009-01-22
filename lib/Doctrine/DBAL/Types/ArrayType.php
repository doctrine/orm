<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps PHP arrays to VARCHAR SQL type.
 *
 * @since 2.0
 */
class ArrayType extends Type
{
    
    
    public function getName()
    {
        return 'Array';
    }
}


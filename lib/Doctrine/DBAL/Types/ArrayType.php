<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps a PHP array to a VARCHAR SQL type.
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
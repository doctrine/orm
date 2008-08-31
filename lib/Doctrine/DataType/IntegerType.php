<?php

/**
 * Type that maps an SQL INT/MEDIUMINT/BIGINT to a PHP integer.
 *
 */
class Doctrine_DataType_IntegerType extends Doctrine_DataType
{
    
    
    public function getSqlDeclaration(array $fieldDeclaration, Doctrine_DatabasePlatform $platform)
    {
        
    }
}

?>
<?php

/**
 * Type that maps an SQL INT/MEDIUMINT/BIGINT to a PHP integer.
 *
 */
class Doctrine_DBAL_Types_IntegerType extends Doctrine_DBAL_Types_Type
{
    
    
    public function getSqlDeclaration(array $fieldDeclaration, Doctrine_DatabasePlatform $platform)
    {
        
    }
}

?>
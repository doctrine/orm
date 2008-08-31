<?php

#namespace Doctrine::DBAL::Types;

/**
 * Type that maps an SQL VARCHAR to a PHP string.
 *
 * @since 2.0
 */
class Doctrine_DataType_StringType extends Doctrine_DataType
{
    public function getSqlDeclaration(array $fieldDeclaration, Doctrine_DatabasePlatform $platform)
    {
        return $platform->getVarcharDeclaration($fieldDeclaration);
    }
    
    public function getDefaultLength(Doctrine_DatabasePlatform $platform)
    {
        return $platform->getVarcharDefaultLength();
    }
    
    public function getName()
    {
        return 'string';
    }
}

?>
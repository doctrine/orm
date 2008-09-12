<?php

/**
 * Type that maps an SQL CLOB to a PHP string.
 *
 * @since 2.0
 */
class Doctrine_DBAL_Types_TextType extends Doctrine_DBAL_Types_Type
{
    /**
     * Enter description here...
     *
     * @param array $fieldDeclaration
     * @param Doctrine_DatabasePlatform $platform
     * @return unknown
     * @override
     */
    public function getSqlDeclaration(array $fieldDeclaration, Doctrine_DatabasePlatform $platform)
    {
        return $platform->getClobDeclarationSql($fieldDeclaration);
    }
    
}

?>
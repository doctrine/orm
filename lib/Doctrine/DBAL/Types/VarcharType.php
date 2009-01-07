<?php

#namespace Doctrine\DBAL\Types;

/**
 * Type that maps an SQL VARCHAR to a PHP string.
 *
 * @since 2.0
 */
class Doctrine_DBAL_Types_VarcharType extends Doctrine_DBAL_Types_Type
{
    /** @override */
    public function getSqlDeclaration(array $fieldDeclaration, Doctrine_DBAL_Platforms_AbstractPlatform $platform)
    {
        return $platform->getVarcharDeclarationSql($fieldDeclaration);
    }

    /** @override */
    public function getDefaultLength(Doctrine_DBAL_Platforms_AbstractPlatform $platform)
    {
        return $platform->getVarcharDefaultLength();
    }

    /** @override */
    public function getName() { return 'Varchar'; }
}


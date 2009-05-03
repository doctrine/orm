<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps an SQL VARCHAR to a PHP string.
 *
 * @since 2.0
 */
class VarcharType extends Type
{
    /** @override */
    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSql($fieldDeclaration);
    }

    /** @override */
    public function getDefaultLength(\Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getVarcharDefaultLength();
    }

    /** @override */
    public function getName()
    {
        return 'Varchar';
    }
}
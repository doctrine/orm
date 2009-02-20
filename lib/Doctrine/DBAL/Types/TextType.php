<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps an SQL CLOB to a PHP string.
 *
 * @since 2.0
 */
class TextType extends Type
{
    /** @override */
    public function getSqlDeclaration(array $fieldDeclaration, Doctrine_DatabasePlatform $platform)
    {
        return $platform->getClobDeclarationSql($fieldDeclaration);
    }
}
<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Type that maps an SQL boolean to a PHP boolean.
 *
 * @since 2.0
 */
class BooleanType extends Type
{
    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getBooleanTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $platform->convertBooleans($value);
    }
    
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return (bool) $value;
    }

    public function getName()
    {
        return 'boolean';
    }
}
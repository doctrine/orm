<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps an SQL DECIMAL to a PHP double.
 *
 * @since 2.0
 */
class DecimalType extends Type
{
    public function getName()
    {
        return 'Decimal';
    }

    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getDecimalTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return (double) $value;
    }
}
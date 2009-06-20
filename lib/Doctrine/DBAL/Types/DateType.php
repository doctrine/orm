<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps an SQL DATETIME to a PHP Date object.
 *
 * @since 2.0
 */
class DateType extends Type
{
    public function getName()
    {
        return 'Date';
    }

    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getDateTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $value->format($platform->getDateFormatString());
    }
    
    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return \DateTime::createFromFormat($platform->getDateFormatString(), $value);
    }
}
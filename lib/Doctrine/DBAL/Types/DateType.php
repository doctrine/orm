<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

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

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getDateTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value->format($platform->getDateFormatString());
    }
    
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return \DateTime::createFromFormat($platform->getDateFormatString(), $value);
    }
}
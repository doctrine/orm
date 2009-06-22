<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Type that maps an SQL DATETIME to a PHP DateTime object.
 *
 * @since 2.0
 */
class DateTimeType extends Type
{
    public function getName()
    {
        return 'DateTime';
    }

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getDateTimeTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value->format($platform->getDateTimeFormatString());
    }
    
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return \DateTime::createFromFormat($platform->getDateTimeFormatString(), $value);
    }
}
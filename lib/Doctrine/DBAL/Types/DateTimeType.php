<?php

namespace Doctrine\DBAL\Types;

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

    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getDateTimeTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $value->format($platform->getDateTimeFormatString());
    }
    
    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return \DateTime::createFromFormat($platform->getDateTimeFormatString(), $value);
    }
}
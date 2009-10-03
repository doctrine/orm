<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Type that maps an SQL DATE to a PHP Date object.
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
        return ($value !== null) 
            ? $value->format($platform->getDateFormatString()) : null;
    }
    
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return ($value !== null) 
            ? \DateTime::createFromFormat($platform->getDateFormatString(), $value) : null;
    }
}
<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Type that maps an SQL DATETIME to a PHP Date object.
 *
 * @since 2.0
 */
class TimeType extends Type
{
    public function getName()
    {
        return 'Time';
    }

    /**
     * {@inheritdoc}
     */
    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getTimeTypeDeclarationSql($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value->format($platform->getTimeFormatString());
    }
    
    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return \DateTime::createFromFormat($platform->getTimeFormatString(), $value);
    }
}
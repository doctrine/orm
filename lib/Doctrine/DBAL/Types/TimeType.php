<?php

namespace Doctrine\DBAL\Types;

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
    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getTimeTypeDeclarationSql($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function convertToDatabaseValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $value->format($platform->getTimeFormatString());
    }
    
    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return \DateTime::createFromFormat($platform->getTimeFormatString(), $value);
    }
}
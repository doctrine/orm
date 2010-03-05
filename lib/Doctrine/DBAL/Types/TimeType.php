<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Type that maps an SQL TIME to a PHP DateTime object.
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
        return $platform->getTimeTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return ($value !== null) 
            ? $value->format($platform->getTimeFormatString()) : null;
    }
    
    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return ($value !== null) 
            ? \DateTime::createFromFormat($platform->getTimeFormatString(), $value) : null;
    }
}
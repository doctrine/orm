<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps an SQL boolean to a PHP boolean.
 *
 * @since 2.0
 */
class BooleanType extends Type
{
    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function convertToDatabaseValue($value, Doctrine_DatabasePlatform $platform)
    {
        return $platform->convertBooleans($value);
    }
    
    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function convertToObjectValue($value)
    {
        return (bool) $value;
    }
}
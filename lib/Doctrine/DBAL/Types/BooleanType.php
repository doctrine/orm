<?php

/**
 * Type that maps an SQL boolean to a PHP boolean.
 *
 */
class Doctrine_DBAL_Types_BooleanType extends Doctrine_DBAL_Types_Type
{
    /**
     * Enter description here...
     *
     * @param unknown_type $value
     * @override
     */
    public function convertToDatabaseValue($value, Doctrine_DatabasePlatform $platform)
    {
        return $platform->convertBooleans($value);
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $value
     * @return unknown
     * @override
     */
    public function convertToObjectValue($value)
    {
        return (bool)$value;
    }
}


<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps an SQL DATETIME to a PHP DateTime object.
 *
 * @since 2.0
 */
class DateTimeType extends Type
{
    /**
     * Enter description here...
     *
     * @param unknown_type $value
     * @param Doctrine_DatabasePlatform $platform
     * @override
     */
    public function convertToDatabaseValue($value, Doctrine_DatabasePlatform $platform)
    {
        //TODO: howto? dbms specific? delegate to platform?
    }
    
    /**
     * Enter description here...
     *
     * @param string $value
     * @return DateTime
     * @override
     */
    public function convertToObjectValue($value)
    {
        return new DateTime($value);
    }
}

?>
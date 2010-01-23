<?php

namespace Doctrine\DBAL;

class DBALException extends \Exception
{
    public static function notSupported($method)
    {
        return new self("Operation '$method' is not supported.");
    }

    public static function invalidPlatformSpecified()
    {
        return new self(
            "Invalid 'platform' option specified, need to give an instance of ".
            "\Doctrine\DBAL\Platforms\AbstractPlatform.");
    }

    public static function invalidPdoInstance()
    {
        return new self(
            "The 'pdo' option was used in DriverManager::getConnection() but no ".
            "instance of PDO was given."
        );
    }

    public static function driverRequired()
    {
        return new self("The options 'driver' or 'driverClass' are mandatory if no PDO ".
            "instance is given to DriverManager::getConnection().");
    }

    public static function unknownDriver($unknownDriverName, array $knownDrivers)
    {
        return new self("The given 'driver' ".$unknownDriverName." is unknown, ".
            "Doctrine currently supports only the following drivers: ".implode(", ", $knownDrivers));
    }

    public static function invalidWrapperClass($wrapperClass)
    {
        return new self("The given 'wrapperClass' ".$wrapperClass." has to be a ".
            "subtype of \Doctrine\DBAL\Connection.");
    }

    public static function invalidDriverClass($driverClass)
    {
        return new self("The given 'driverClass' ".$driverClass." has to implement the ".
            "\Doctrine\DBAL\Driver interface.");
    }
}
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
        return new self("Invalid 'platform' option specified, need to give an instance of \Doctrine\DBAL\Platforms\AbstractPlatform.");
    }
}
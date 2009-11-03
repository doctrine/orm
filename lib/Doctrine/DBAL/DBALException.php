<?php

namespace Doctrine\DBAL;

class DBALException extends \Exception
{
    public static function notSupported($method)
    {
        return new self("Operation '$method' is not supported.");
    }
}
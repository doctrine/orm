<?php

namespace Doctrine\ORM\Tools\Export;

use Doctrine\ORM\ORMException;

class ExportException extends ORMException
{
    public static function invalidExporterDriverType($type)
    {
        return new self("The specified export driver '$type' does not exist");
    }

    public static function invalidMappingDriverType($type)
    {
        return new self("The mapping driver '$type' does not exist");
    }
}
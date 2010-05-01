<?php

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\ORMException;

class ToolsException extends ORMException
{
    public static function couldNotMapDoctrine1Type($type)
    {
        return new self("Could not map doctrine 1 type '$type'!");
    }
}
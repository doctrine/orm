<?php

namespace Doctrine\ORM;

class ORMException extends \Exception
{
    public static function entityMissingAssignedId($entity)
    {
        return new self("Entity of type " . get_class($entity) . " is missing an assigned ID.");
    }
}

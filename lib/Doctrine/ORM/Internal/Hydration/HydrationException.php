<?php

namespace Doctrine\ORM\Internal\Hydration;

class HydrationException extends \Doctrine\ORM\ORMException
{
    public static function nonUniqueResult()
    {
        return new self("The result returned by the query was not unique.");
    }
    
    public static function parentObjectOfRelationNotFound($alias, $parentAlias)
    {
        return new self("The parent object of entity result with alias '$alias' was not found."
                . " The parent alias is '$parentAlias'.");
    }
}
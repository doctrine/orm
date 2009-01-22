<?php

namespace Doctrine\ORM\Exceptions;

class HydrationException extends \Doctrine\Common\DoctrineException
{
    
    public static function nonUniqueResult()
    {
        return new self("The result returned by the query was not unique.");
    }
    
}


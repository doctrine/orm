<?php

class Doctrine_ORM_Exceptions_HydrationException extends Doctrine_ORM_Exceptions_ORMException
{
    
    public static function nonUniqueResult()
    {
        return new self("The result returned by the query was not unique.");
    }
    
}

?>
<?php 

class Doctrine_EntityManager_Exception extends Doctrine_Exception
{
    public static function invalidFlushMode()
    {
        return new self("Invalid flush mode.");
    }
    
    public static function noEntityManagerAvailable()
    {
        return new self("No EntityManager available.");
    }
    
    public static function entityAlreadyBound($entityName)
    {
        return new self("The entity '$entityName' is already bound.");
    }
    
    public static function noManagerWithName($emName)
    {
        return new self("EntityManager named '$emName' not found.");
    }
}
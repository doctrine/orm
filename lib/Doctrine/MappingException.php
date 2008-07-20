<?php

/**
 * A MappingException indicates that something is wrong with the mapping setup.
 *
 * @since 2.0
 */
class Doctrine_MappingException extends Doctrine_Exception
{
    public static function identifierRequired($entityName)
    {
        return new self("No identifier specified for Entity '$entityName'."
                . " Every Entity must have an identifier.");
    }
    
    public static function invalidInheritanceType($type)
    {
        return new self("The inheritance type '$type' does not exist.");
    }
    
    public static function invalidInheritanceOption($name)
    {
        return new self("The inheritance option '$name' does not exist.");
    }
    
    public static function generatorNotAllowedWithCompositeId()
    {
        return new self("Id generators can't be used with a composite id.");
    }
    
}

?>
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
        return new self("No identifier/primary key specified for Entity '$entityName'."
                . " Every Entity must have an identifier/primary key.");
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
    
    public static function missingFieldName()
    {
        return new self("The association mapping misses the 'fieldName' attribute.");
    }
    
    public static function missingTargetEntity($fieldName)
    {
        return new self("The association mapping '$fieldName' misses the 'targetEntity' attribute.");
    }
    
    public static function missingSourceEntity($fieldName)
    {
        return new self("The association mapping '$fieldName' misses the 'sourceEntity' attribute.");
    }
    
    public static function mappingNotFound($fieldName)
    {
        return new self("No mapping found for field '$fieldName'.");
    }
}

?>
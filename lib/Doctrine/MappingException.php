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
}

?>
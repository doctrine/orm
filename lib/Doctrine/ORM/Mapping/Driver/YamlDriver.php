<?php 

/**
 * The yaml driver loads metadata informations about classes from .yml files.
 *
 */
class Doctrine_ORM_Mapping_Driver_YamlDriver
{
    /**
     * 
     */
    public function loadMetadataForClass($className, Doctrine_ORM_Mapping_ClassMetadata $metadata)
    {
        throw new Doctrine_Exception("YAML driver not yet implemented.");
    }   
}
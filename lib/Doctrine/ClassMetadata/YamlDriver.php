<?php 

/**
 * The yaml driver loads metadata informations about classes from .yml files.
 *
 */
class Doctrine_ClassMetadata_YamlDriver
{
    /**
     * 
     */
    public function loadMetadataForClass($className, Doctrine_ClassMetadata $metadata)
    {
        throw new Doctrine_ClassMetadata_Exception("YAML driver not yet implemented.");
    }   
}
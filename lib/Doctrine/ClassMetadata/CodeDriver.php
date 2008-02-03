<?php 

/**
 * The code metadata driver loads the metadata of the classes through invoking 
 * a static callback method that needs to be implemented when using this driver.
 *
 * @author Roman Borschel <roman@code-factory.org>
 */
class Doctrine_ClassMetadata_CodeDriver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     */
    public function loadMetadataForClass($className, Doctrine_ClassMetadata $metadata)
    {
        call_user_func_array(array($className, 'initMetadata'), array($metadata));
    }   
}
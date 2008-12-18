<?php

#namespace Doctrine\Common;

/**
 * Description of ClassMetadataFactory
 *
 * @author robo
 */
class Doctrine_Common_ClassMetadataFactory {

    /** The factory driver. */
    protected $_driver;
    /** Map of the already loaded ClassMetadata instances, indexed by class name. */
    protected $_loadedMetadata = array();

    /**
     * Creates a new factory instance that uses the given metadata driver implementation.
     *
     * @param $driver The metadata driver to use.
     */
    public function __construct($driver)
    {
        $this->_driver = $driver;
    }

    /**
     * Returns the metadata object for a class.
     *
     * @param string $className  The name of the class.
     * @return Doctrine_Metadata
     */
    public function getMetadataFor($className)
    {
        if ( ! isset($this->_loadedMetadata[$className])) {
            $this->_loadMetadata($className);
            $this->_validateAndCompleteMetadata($className);
        }
        return $this->_loadedMetadata[$className];
    }

    /**
     * Loads the metadata for the given class.
     *
     * @param <type> $className
     * @return <type>
     */
    protected function _loadMetadata($className)
    {
        $classMetadata = new Doctrine_Common_ClassMetadata();
        $this->_driver->loadMetadataForClass($className, $classMetadata);
        return $classMetadata;
    }

    /** Template method for subclasses */
    protected function _validateAndCompleteMetadata($className)
    { /*empty*/ }
}
?>

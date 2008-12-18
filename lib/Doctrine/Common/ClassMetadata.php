<?php

#namespace Doctrine\Common;

/**
 * The ClassMetadata class represents a generic container for metadata of a class.
 *
 * @author robo
 */
class Doctrine_Common_ClassMetadata
{
    /** The metadata that applies to the class. */
    protected $_classMetadata = array();
    /** The metadata that applies to properties of the class. */
    protected $_fieldMetadata = array();
    protected $_entityName;

    /**
     * 
     *
     * @param <type> $className
     */
    public function __construct($className)
    {
        $this->_entityName = $className;
    }

    /**
     * Adds metadata to a property of the class.
     *
     * @param string $fieldName
     * @param array $fieldMetadata
     */
    public function addFieldMetadata($fieldName, array $fieldMetadata)
    {
        $this->_fieldMetadata[$fieldName] = array_merge(
                isset($this->_fieldMetadata[$fieldName]) ? $this->_fieldMetadata[$fieldName] : array(),
                $fieldMetadata);
    }

    /**
     * 
     *
     * @param <type> $fieldName
     * @param <type> $metadata
     */
    public function setFieldMetadata($fieldName, array $metadata)
    {
        $this->_fieldMetadata[$fieldName] = $metadata;
    }

    /**
     *
     * @param <type> $fieldName
     * @param <type> $metadataKey
     * @param <type> $value
     */
    public function setFieldMetadataEntry($fieldName, $metadataKey, $value)
    {
        $this->_fieldMetadata[$fieldName][$metadataKey] = $value;
    }

    /**
     * Gets metadata of a property of the class.
     *
     * @param string $fieldName
     * @param string $metadataKey
     * @return mixed
     */
    public function getFieldMetadata($fieldName)
    {
        return $this->_fieldMetadata[$fieldName];
    }

    /**
     *
     * @param <type> $fieldName
     * @param <type> $metadataKey
     * @return <type> 
     */
    public function getFieldMetadataEntry($fieldName, $metadataKey)
    {
        return isset($this->_fieldMetadata[$fieldName][$metadataKey]) ?
                $this->_fieldMetadata[$fieldName][$metadataKey] : null;
    }

    /**
     * Gets metadata of the class.
     *
     * @param string $metadataKey
     * @return mixed
     */
    public function getClassMetadata()
    {
        return $this->_classMetadata;
    }

    /**
     * 
     *
     * @param <type> $metadataKey
     */
    public function getClassMetadataEntry($metadataKey)
    {
        return isset($this->_classMetadata[$metadataKey]) ?
                $this->_classMetadata[$metadataKey] : null;
    }

    /**
     * Adds metadata to the class.
     *
     * @param array $classMetadata
     */
    public function addClassMetadata(array $classMetadata)
    {
        $this->_classMetadata = array_merge($this->_classMetadata, $classMetadata);
    }

    /**
     * 
     *
     * @param <type> $metadata
     */
    public function setClassMetadata(array $metadata)
    {
        $this->_classMetadata = $metadata;
    }

    /**
     *
     * @param <type> $metadataKey
     * @param <type> $value 
     */
    public function setClassMetadataEntry($metadataKey, $value)
    {
        $this->_classMetadata[$metadataKey] = $value;
    }
}
?>

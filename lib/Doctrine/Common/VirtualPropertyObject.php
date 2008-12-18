<?php

#namespace Doctrine\Common;

#use \ArrayAccess;

/**
 * Base class for classes that use the virtual property system.
 *
 * @author robo
 */
class Doctrine_Common_VirtualPropertyObject implements ArrayAccess
{
    protected $_data = array();
    protected $_entityName;

    /**
     * Initializes a new instance of a class derived from VirtualPropertyObject.
     */
    public function __construct()
    {
        $this->_entityName = get_class($this);
        if ( ! Doctrine_Common_VirtualPropertySystem::isInitialized($this->_entityName)) {
            Doctrine_Common_VirtualPropertySystem::initialize($this->_entityName);
        }
    }

    /**
     * Generic getter for virtual properties.
     *
     * @param string $fieldName  Name of the field.
     * @return mixed
     */
    final public function get($fieldName)
    {
        if ( ! Doctrine_Common_VirtualPropertySystem::hasProperty($this->_entityName, $fieldName)) {
            throw new Doctrine_Exception("Access of undefined property '$fieldName'.");
        }
        $getter = $this->_getCustomAccessor($fieldName);
        if ($getter) {
            return $this->$getter();
        }
        return $this->_get($fieldName);
    }

    /**
     * Generic setter for virtual properties.
     *
     * @param string $name  The name of the field to set.
     * @param mixed $value  The value of the field.
     */
    final public function set($fieldName, $value)
    {
        if ( ! Doctrine_Common_VirtualPropertySystem::hasProperty($this->_entityName, $fieldName)) {
            throw new Doctrine_Exception("Access of undefined property '$fieldName'.");
        }
        if (Doctrine_Common_VirtualPropertySystem::isTypeCheckEnabled()) {
            $this->_checkType($fieldName, $value);
        }
        $setter = $this->_getCustomMutator($fieldName);
        if ($setter) {
            return $this->$setter($value);
        }
        $this->_set($fieldName, $value);
    }

    /**
     * Checks the type of a virtual property.
     *
     * @param <type> $fieldName
     * @param <type> $value
     */
    protected function _checkType($fieldName, $value)
    {
        $type = Doctrine_Common_VirtualPropertySystem::getType($this->_entityName, $fieldName);
        if (Doctrine_Common_VirtualPropertySystem::isSimplePHPType($type)) {
            $is_type = "is_$type";
            if ( ! $is_type($value)) {
                throw new Doctrine_Exception("'$value' is of an invalid type. Expected: $type.");
            }
        } else if ($type == 'array') {
            if ( ! is_array($value)) {
                throw new Doctrine_Exception("'$value' is of an invalid type. Expected: array.");
            }
        } else {
            if ( ! $value instanceof $type) {
                throw new Doctrine_Exception("'$value' is of an invalid type. Expected: $type.");
            }
        }
    }

    protected function _get($fieldName)
    {
        return isset($this->_data[$fieldName]) ? $this->_data[$fieldName] : null;
    }

    protected function _set($fieldName, $value)
    {
        $this->_data[$fieldName] = $value;
    }

    /**
     * Gets the custom mutator method for a virtual property, if it exists.
     *
     * @param string $fieldName  The field name.
     * @return mixed  The name of the custom mutator or FALSE, if the field does
     *                not have a custom mutator.
     */
    private function _getCustomMutator($fieldName)
    {
        if (Doctrine_Common_VirtualPropertySystem::getMutator($this->_entityName, $fieldName) === null) {
            if (Doctrine_Common_VirtualPropertySystem::isAutoAccessorOverride()) {
                $setterMethod = 'set' . Doctrine::classify($fieldName);
                if ( ! method_exists($this, $setterMethod)) {
                    $setterMethod = false;
                }
                Doctrine_Common_VirtualPropertySystem::setMutator(
                        $this->_entityName, $fieldName, $setterMethod);
            } else {
                Doctrine_Common_VirtualPropertySystem::setMutator(
                        $this->_entityName, $fieldName, false);
            }
        }
        return Doctrine_Common_VirtualPropertySystem::getMutator($this->_entityName, $fieldName);
    }

    /**
     * Gets the custom accessor method of a virtual property, if it exists.
     *
     * @param string $fieldName  The field name.
     * @return mixed  The name of the custom accessor method, or FALSE if the
     *                field does not have a custom accessor.
     */
    private function _getCustomAccessor($fieldName)
    {
        if (Doctrine_Common_VirtualPropertySystem::getAccessor($this->_entityName, $fieldName) === null) {
            if (Doctrine_Common_VirtualPropertySystem::isAutoAccessorOverride()) {
                $getterMethod = 'get' . Doctrine::classify($fieldName);
                if ( ! method_exists($this, $getterMethod)) {
                    $getterMethod = false;
                }
                Doctrine_Common_VirtualPropertySystem::setAccessor(
                        $this->_entityName, $fieldName, $getterMethod);
            } else {
                Doctrine_Common_VirtualPropertySystem::setAccessor(
                        $this->_entityName, $fieldName, false);
            }
        }

        return Doctrine_Common_VirtualPropertySystem::getAccessor($this->_entityName, $fieldName);
    }

    protected function _contains($fieldName)
    {
        return isset($this->_data[$fieldName]);
    }

    protected function _unset($fieldName)
    {
        unset($this->_data[$fieldName]);
    }

    /**
     * Intercepts mutating calls for virtual properties.
     *
     * @see set, offsetSet
     * @param $name
     * @param $value
     * @since 1.0
     * @return void
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Intercepts accessing calls for virtual properties.
     *
     * @see get,  offsetGet
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Intercepts isset() calls for virtual properties.
     *
     * @param string $name
     * @return boolean          whether or not this object contains $name
     */
    public function __isset($name)
    {
        return $this->_contains($name);
    }

    /**
     * Intercepts unset() calls for virtual properties.
     *
     * @param string $name
     * @return void
     */
    public function __unset($name)
    {
        return $this->_unset($name);
    }

    /* ArrayAccess implementation */

    /**
     * Check if an offsetExists.
     *
     * @param mixed $offset
     * @return boolean          whether or not this object contains $offset
     */
    public function offsetExists($offset)
    {
        return $this->_contains($offset);
    }

    /**
     * offsetGet    an alias of get()
     *
     * @see get,  __get
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * sets $offset to $value
     * @see set,  __set
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * unset a given offset
     * @see set, offsetSet, __set
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        return $this->_unset($offset);
    }

    /* END of ArrayAccess implementation */
}
?>

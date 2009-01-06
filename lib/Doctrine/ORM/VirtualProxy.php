<?php
#namespace Doctrine\ORM;

/**
 * Represents a virtual proxy that is used for lazy to-one associations.
 *
 * @author robo
 * @since 2.0
 */
class Doctrine_ORM_VirtualProxy
{
    private $_assoc;
    private $_refProp;
    private $_owner;

    /**
     * Initializes a new VirtualProxy instance that will proxy the specified property on
     * the specified owner entity. The given association is used to lazy-load the
     * real object on access of the proxy.
     *
     * @param <type> $owner
     * @param <type> $assoc
     * @param <type> $refProp
     */
    public function __construct($owner, Doctrine_ORM_Mapping_AssociationMapping $assoc, ReflectionProperty $refProp)
    {
        $this->_owner = $owner;
        $this->_assoc = $assoc;
        $this->_refProp = $refProp;
    }

    private function _load()
    {
        $realInstance = $tis->_assoc->lazyLoadFor($this->_owner);
        $this->_refProp->setValue($this->_owner, $realInstance);
        return $realInstance;
    }

    /** All the "magic" interceptors */

    public function __call($method, $args)
    {
        $realInstance = $this->_load();
        return call_user_func_array(array($realInstance, $method), $args);
    }

    public function __get($prop)
    {
        $realInstance = $this->_load();
        return $realInstance->$prop;
    }

    public function __set($prop, $value)
    {
        $realInstance = $this->_load();
        $realInstance->$prop = $value;
    }

    public function __isset($prop)
    {
        $realInstance = $this->_load();
        return isset($realInstance->$prop);
    }

    public function __unset($prop)
    {
        $realInstance = $this->_load();
        unset($realInstance->$prop);
    }
}
?>

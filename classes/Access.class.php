<?php
/**
 * class Doctrine_Access
 *
 * the purpose of Doctrine_Access is to provice array access 
 * and property overload interface for subclasses
 */
abstract class Doctrine_Access implements ArrayAccess {
    /**
     * setArray
     * @param array $array          an array of key => value pairs
     */
    public function setArray(array $array) {
        foreach($array as $k=>$v):
            $this->set($k,$v);
        endforeach;
    }
    /**
     * __set -- an alias of set()
     * @see set, offsetSet
     * @param $name
     * @param $value
     */
    public function __set($name,$value) {
        $this->set($name,$value);
    }
    /**
     * __get -- an alias of get()
     * @see get,  offsetGet
     * @param mixed $name
     * @return mixed
     */
    public function __get($name) {
        return $this->get($name);
    }
    /**
     * @param mixed $offset
     * @return boolean -- whether or not the data has a field $offset
     */
    public function offsetExists($offset) {
        return (bool) isset($this->data[$offset]);
    }
    /**
     * offsetGet -- an alias of get()
     * @see get,  __get
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->get($offset);
    }
    /**
     * sets $offset to $value
     * @see set,  __set
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) {
        if( ! isset($offset)) {
            $this->add($value);
        } else
            $this->set($offset,$value);
    }
    /**
     * unset a given offset
     * @see set, offsetSet, __set
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        if($this instanceof Doctrine_Collection) {
            return $this->remove($offset);
        } else {
            $this->set($offset,null);
        }
    }
}
?>

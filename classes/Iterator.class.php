<?php
/**
 * Doctrine_Iterator
 * iterates through Doctrine_Collection
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
abstract class Doctrine_Iterator implements Iterator {
    /**
     * @var Doctrine_Collection $collection         
     */
    protected $collection;
    /**
     * @var array $keys
     */
    protected $keys;
    /**
     * @var mixed $key
     */
    protected $key;
    /**
     * @var integer $index
     */
    protected $index;
    /**
     * @var integer $count
     */
    protected $count;

    /**
     * constructor
     * @var Doctrine_Collection $collection
     */
    public function __construct(Doctrine_Collection $collection) {
        $this->collection = $collection;
        $this->keys       = $this->collection->getKeys();
        $this->count      = $this->collection->count();
    }
    /**
     * rewinds the iterator
     *
     * @return void
     */
    public function rewind() {
        $this->index = 0;
        $i = $this->index;
        if(isset($this->keys[$i]))
            $this->key   = $this->keys[$i];
    }

    /**
     * returns the current key
     *
     * @return integer
     */
    public function key() {
        return $this->key;
    }
    /**
     * returns the current record
     *
     * @return Doctrine_Record
     */
    public function current() {
        return $this->collection->get($this->key);
    }
    /**
     * advances the internal pointer
     *
     * @return void
     */
    public function next() {
        $this->index++;
        $i = $this->index;
        if(isset($this->keys[$i]))
            $this->key   = $this->keys[$i];
    }
}


?>

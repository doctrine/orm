<?php
/**
 * Doctrine_Iterator
 * iterates through Doctrine_Collection
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
     * @return void
     */
    public function rewind() {
        $this->index = 0;
        $i = $this->index;
        if(isset($this->keys[$i]))
            $this->key   = $this->keys[$i];
    }

    /**
     * @return integer                          the current key
     */
    public function key() {
        return $this->key;
    }
    /**
     * @return Doctrine_Record                  the current record
     */
    public function current() {
        return $this->collection->get($this->key);
    }
    /**
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

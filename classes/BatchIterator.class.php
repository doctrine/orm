<?php
/**
 * Doctrine_BatchIterator
 * iterates through Doctrine_Collection_Batch
 */
class Doctrine_BatchIterator implements Iterator {
    /**
     * @var Doctrine_Collection_Batch $collection
     */
    private $collection;
    /**
     * @var array $keys
     */
    private $keys;
    /**
     * @var mixed $key
     */
    private $key;
    /**
     * @var integer $index
     */
    private $index;
    /**
     * @var integer $count
     */
    private $count;

    /**
     * constructor
     * @var Doctrine_Collection_Batch $collection
     */
    public function __construct(Doctrine_Collection_Batch $collection) {
        $this->collection = $collection;
        $this->keys  = $this->collection->getKeys();
        $this->count = $this->collection->count();
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
     * @return boolean                          whether or not the iteration will continue
     */
    public function valid() {
        return $this->index < $this->count;
    }
    /**
     * @return integer                          the current key
     */
    public function key() {
        return $this->key;
    }
    /**
     * @return Doctrine_Record                              the current DAO
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

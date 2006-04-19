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
class Doctrine_Iterator_Normal extends Doctrine_Iterator {
    /**
     * @return boolean                          whether or not the iteration will continue
     */
    public function valid() {
        return ($this->index < $this->count);
    }
}
class Doctrine_Iterator_Offset extends Doctrine_Iterator {
    public function valid() { }
}
class Doctrine_Iterator_Expandable extends Doctrine_Iterator {
    public function valid() {
        if($this->index < $this->count)
            return true;
        elseif($this->index == $this->count) {

            $coll  = $this->collection->expand($this->index);

            if($coll instanceof Doctrine_Collection) {
                $count = count($coll);
                if($count > 0) {
                    $this->keys   = array_merge($this->keys, $coll->getKeys());
                    $this->count += $count;
                    return true;
                }
            }

            return false;
        }
    }
}
?>

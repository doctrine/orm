<?php
class Doctrine_Module implements IteratorAggregate, Countable {
    /**
     * @var array $components   an array containing all the components in this module
     */
    protected $components = array();
    /**
     * @var string $name        the name of this module
     */
    private $name;
    /**
     * constructor
     *
     * @param string $name      the name of this module
     */
    public function __construct($name) {
        $this->name = $name;
    }
    /**
     * returns the name of this module
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    /**
     * flush
     * saves all components
     *
     * @return void
     */
    public function flush() {
        $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
        
        $tree = $connection->buildFlushTree($this->components);
    }
    /**
     * getIterator
     * this class implements IteratorAggregate interface
     * returns an iterator that iterates through the components
     * in this module
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->components);
    }
    /**
     * count
     * this class implements Countable interface
     * returns the number of components in this module
     *
     * @return integer
     */
    public function count() {
        return count($this->components);
    }
}


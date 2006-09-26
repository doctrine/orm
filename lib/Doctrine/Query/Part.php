<?php
Doctrine::autoload("Doctrine_Access");

abstract class Doctrine_Query_Part extends Doctrine_Access {
    
    protected $query;
    
    protected $name;
    
    protected $parts = array();

    public function __construct(Doctrine_Query $query) {
        $this->query = $query;

    }
    
    public function getName() {
        return $this->name;
    }

    public function getQuery() {
        return $this->query;
    }
    /** 
     * add
     * 
     * @param string $value
     * @return void
     */
    public function add($value) {
        $method = "parse".$this->name;
        $this->query->$method($value);
    }

    public function get($name) { }
    public function set($name, $value) { }
}




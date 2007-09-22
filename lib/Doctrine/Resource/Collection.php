<?php
class Doctrine_Resource_Collection extends Doctrine_Access implements Countable, IteratorAggregate
{
    public $data = array();
    public $config = array();
    public $model = null;
    
    public function __construct($model, $config)
    {
        $this->model = $model;
        $this->config = $config;
    }
    
    public function count()
    {
        return count($data);
    }
    
    public function get($get)
    {
        if (isset($this->data[$get])) {
            return $this->data[$get];
        }
    }

    public function set($set, $value)
    {
        $this->data[$set] = $value;
    }
    
    public function getIterator()
    {
        $data = $this->data;
        
        return new ArrayIterator($data);
    }
    
    public function getFirst()
    {
        return isset($this->data[0]) ? $this->data[0]:null;
    }
    
    public function toArray()
    {
        $array = array();
        
        foreach ($this->data as $key => $record) {
            $array[$key] = $record->toArray();
        }
        
        return $array;
    }
    
    public function save()
    {
        foreach ($this->data as $record) {
            $record->save();
        }
    }
}
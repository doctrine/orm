<?php
class Doctrine_Resource_Collection extends Doctrine_Access implements Countable, IteratorAggregate
{
    public $data = array();
    public $config = array();
    public $model = null;
    
    public function __construct($model)
    {
        $this->model = $model;
    }
    
    public function count()
    {
        return count($data);
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
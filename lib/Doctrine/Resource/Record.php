<?php
class Doctrine_Resource_Record extends Doctrine_Record_Abstract implements Countable, IteratorAggregate
{
    public $data = array();
    public $config = array();
    public $model = null;
    public $changes = array();
    
    public function get($get)
    {
        if (!isset($this->data[$get])) {
            $this->data[$get] = null;
        }
        
        return $this->data[$get];
    }

    public function set($set, $value)
    {
        $this->data[$set] = $value;
        
        $this->changes[$set] = $value;
    }
    
    public function count()
    {
        return count($this->data);
    }
    
    public function getIterator()
    {
        $data = $this->data;
        
        return new ArrayIterator($data);
    }
    
    public function save()
    {
        $request = array();
        $request['format'] = $this->config['format'];
        $request['type'] = 'save';
        $request['model'] = $this->model;
        $request['data'] = $this->data;
        $request['changes'] = $this->changes;
        
        $response = Doctrine_Resource::request($this->config['url'], $request);
        
        $array = Doctrine_Parser::load($response, $request['format']);
    }
    
    public function toArray()
    {
        $array = array();
        
        foreach ($this->data as $key => $value) {
            if ($value instanceof Doctrine_Resource_Collection) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
}
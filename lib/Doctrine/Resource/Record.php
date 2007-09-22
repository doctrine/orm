<?php
class Doctrine_Resource_Record extends Doctrine_Record_Abstract implements Countable, IteratorAggregate
{
    public $data = array();
    public $config = array();
    public $model = null;
    
    public function __construct($model, $config)
    {
        $this->model = $model;
        $this->config = $config;
    }
    
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
    
    public function newRequest($type)
    {
        $request = array();
        $request['format'] = isset($this->config['format']) ? $this->config['format']:'xml';
        $request['type'] = $type;
        $request['model'] = $this->model;
        
        return $request;
    }
    
    public function save()
    {
        $request = $this->newRequest('save');
        $request['data'] = $this->toArray();
        
        $response = Doctrine_Resource::request($this->config['url'], $request);
        
        $array = Doctrine_Parser::load($response, $request['format']);
        
        $resource = new Doctrine_Resource();
        $this->data = $resource->hydrate(array($array), $this->model, $this->config)->getFirst()->data;
    }
    
    public function toArray()
    {
        $array = array();
        
        foreach ($this->data as $key => $value) {
            if ($value instanceof Doctrine_Resource_Collection OR $value instanceof Doctrine_Resource_Record) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
}
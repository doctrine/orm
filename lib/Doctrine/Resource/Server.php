<?php
class Doctrine_Resource_Server extends Doctrine_Resource
{
    public $config = array();
    public $format = 'xml';
    
    public function __construct($config)
    {
        $this->config = array_merge($config, $this->config);
    }
    
    public function executeSave($request)
    {
        $model = $request['model'];
        $data = $request['data'];
        
        $record = new $model();
        $record->fromArray($data);
        $record->save();
        
        return $record->toArray(true, true);
    }
    
    public function executeQuery($request)
    {
        $dql = $request['dql'];
        $params = isset($request['params']) ? $request['params']:array();
        
        $conn = Doctrine_Manager::connection();
        
        return $conn->query($dql, $params)->toArray(true, true);
    }
    
    public function execute($request)
    {
        if (!isset($request['type'])) {
            throw new Doctrine_Resource_Exception('You must specify a request type: query or save');
        }
        
        $format = isset($request['format']) ? $request['format']:'xml';
        $type = $request['type'];
        $funcName = 'execute' . Doctrine::classify($type);
        
        $result = $this->$funcName($request);
        
        return Doctrine_Parser::dump($result, $format);
    }
    
    public function run($request)
    {
        echo $this->execute($request);
    }
}
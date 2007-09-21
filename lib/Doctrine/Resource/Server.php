<?php
class Doctrine_Resource_Server extends Doctrine_Resource
{
    public $config = array();
    
    public function __construct($config)
    {
        $this->config = array_merge($config, $this->config);
    }
    
    public function execute($request)
    {
        if (!isset($request['type'])) {
            throw new Doctrine_Resource_Exception('You must specify a request type: query or save');
        }
        
        $format = isset($request['format']) ? $request['format']:'xml';
        
        if ($request['type'] == 'query') {
            if (isset($request['dql']) && $request['dql']) {
                $dql = $request['dql'];
                $params = isset($request['params']) ? $request['params']:array();
                
                $conn = Doctrine_Manager::connection();
                $result = $conn->query($dql, $params, Doctrine::FETCH_ARRAY);
            } else {
                throw new Doctrine_Resource_Exception('You must specify a dql query');
            }
        } else if ($request['type'] == 'save') {
            $table = Doctrine_Manager::getInstance()->getTable($request['model']);
            $pks = (array) $table->getIdentifier();
            $pks = array_flip($pks);
            
            foreach (array_keys($pks) as $key) {
                $pks[$key] = $request['data'][$key];
            }
            
            $record = $table->find($pks);
            
            $changes = $request['changes'];
            
            foreach ($changes as $key => $value) {
                $record->$key = $value;
            }
            
            $record->save();
            
            $result = array('success' => true);
        }
        
        return Doctrine_Parser::dump($result, $format);
    }
    
    public function run($request)
    {
        echo $this->execute($request);
    }
}
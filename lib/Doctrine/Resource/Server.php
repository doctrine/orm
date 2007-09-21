<?php
class Doctrine_Resource_Server extends Doctrine_Resource
{
    public function execute($request)
    {
        if (isset($request['dql'])) {
            $query = new Doctrine_Query();
            $result = $query->query($request['dql']);
        } else {
            $result = $this->buildDql($request['parts']);
        }
        
        $data = array();
        foreach ($result as $recordKey => $record) {
            $array = $record->toArray();
            
            $recordKey = get_class($record). '_' .($recordKey + 1);
            
            foreach ($array as $valueKey => $value) {
                $data[get_class($record)][$recordKey][$valueKey] = $value;
            }
        }
        
        $format = isset($request['format']) ? $request['format']:'xml';
        
        return Doctrine_Parser::dump($data, $format);
    }
    
    public function buildDql($parts)
    {
        
    }
    
    public function run()
    {
        $request = $_REQUEST;
        
        echo $this->execute($request);
    }
}
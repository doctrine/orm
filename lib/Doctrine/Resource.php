<?php
class Doctrine_Resource
{
    public static function request($url, $request)
    {   
        $url .= strstr($url, '?') ? '&':'?';
        $url .= http_build_query($request);
        
        $response = file_get_contents($url);
        
        return $response;
    }
    
    public function hydrate(array $array, $model, $config, $passedKey = null)
    {
        $collection = new Doctrine_Resource_Collection($model, $config);
        
        foreach ($array as $record) {
            $r = new Doctrine_Resource_Record($model, $config);
            
            foreach ($record as $key => $value) {
                if (is_array($value)) {
                    $r->data[$key] = $this->hydrate($value, $model, $config, $key);
                } else {
                    $r->data[$key] = $value;
                }
            }
        
            $collection->data[] = $r;
        }
        
        return $collection;
    }
}
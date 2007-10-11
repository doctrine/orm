<?php
class Doctrine_Config
{
    protected $connections = array();
    protected $cliConfig = array();
    
    public function addConnection($adapter, $name = null)
    {
        $connections[] = Doctrine_Manager::getInstance()->openConnection($adapter, $name);
    }
    
    public function bindComponent($modelName, $connectionName)
    {
        return Doctrine_Manager::getInstance()->bindComponent($modelName, $connectionName);
    }
    
    public function setAttribute($key, $value)
    {
        foreach ($this->connections as $connection) {   
            $connection->setAttribute($key, $value);
        }
    }
    
    public function addCliConfig($key, $value)
    {
        $this->cliConfig[$key] = $value;
    }
    
    public function getCliConfig()
    {
       return $this->cliConfig;
    }
}
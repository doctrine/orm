<?php

#namespace Doctrine::DBAL;

class Doctrine_ConnectionFactory
{
     private $_drivers = array(
            'mysql'    => 'Doctrine_Connection_Mysql',
            'sqlite'   => 'Doctrine_Connection_Sqlite',
            'pgsql'    => 'Doctrine_Connection_Pgsql',
            'oci'      => 'Doctrine_Connection_Oracle',
            'oci8'     => 'Doctrine_Connection_Oracle',
            'oracle'   => 'Doctrine_Connection_Oracle',
            'mssql'    => 'Doctrine_Connection_Mssql',
            'dblib'    => 'Doctrine_Connection_Mssql',
            'firebird' => 'Doctrine_Connection_Firebird',
            'informix' => 'Doctrine_Connection_Informix',
            'mock'     => 'Doctrine_Connection_Mock');
    
    public function __construct()
    {
        
    }
    
    public function createConnection(array $params)
    {
        $this->_checkParams($params);
        $className = $this->_drivers[$params['driver']];
        return new $className($params);
    }
    
    private function _checkParams(array $params)
    {
        // check existance of mandatory parameters
        
        // driver
        if ( ! isset($params['driver'])) {
            throw Doctrine_ConnectionFactory_Exception::driverRequired();
        }
        // user
        if ( ! isset($params['user'])) {
            throw Doctrine_ConnectionFactory_Exception::userRequired();
        }
        // password
        if ( ! isset($params['password'])) {
            throw Doctrine_ConnectionFactory_Exception::passwordRequired();
        }
        
        // check validity of parameters
        
        // driver
        if ( ! isset($this->_drivers[$params['driver']])) {
            throw Doctrine_ConnectionFactory_Exception::unknownDriver($driverName);
        }
        // existing pdo object
        if (isset($params['pdo']) && ! $params['pdo'] instanceof PDO) {
            throw Doctrine_ConnectionFactory_Exception::invalidPDOInstance();
        }
    }
}

?>
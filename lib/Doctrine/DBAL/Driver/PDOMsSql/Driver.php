<?php

#namespace Doctrine::DBAL::Driver::PDOMySql;

#use Doctrine::DBAL::Driver;

class Doctrine_DBAL_Driver_PDOMsSql_Driver implements Doctrine_DBAL_Driver
{
    
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new Doctrine_DBAL_Driver_MsSql_Connection(
                $this->_constructPdoDsn($params),
                $username,
                $password,
                $driverOptions);
    }
    
    /**
     * Constructs the MySql PDO DSN.
     * 
     * Overrides Connection#_constructPdoDsn().
     *
     * @return string  The DSN.
     */
    private function _constructPdoDsn(array $params)
    {
        //TODO
    }
    
    public function getDatabasePlatform()
    {
        return new Doctrine_DatabasePlatform_MySqlPlatform();
    }
    
    public function getSchemaManager(Doctrine_Connection $conn)
    {
        return new Doctrine_Schema_MySqlSchemaManager($conn);
    }
    
}

?>
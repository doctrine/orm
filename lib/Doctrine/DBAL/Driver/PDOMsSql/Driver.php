<?php

namespace Doctrine\DBAL\Driver\PDOMsSql;

class Driver implements \Doctrine\DBAL\Driver
{
    
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new Connection(
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


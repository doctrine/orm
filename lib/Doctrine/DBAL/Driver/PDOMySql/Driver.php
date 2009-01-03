<?php

#namespace Doctrine\DBAL\Driver\PDOMySql;

#use Doctrine\DBAL\Driver;

class Doctrine_DBAL_Driver_PDOMySql_Driver implements Doctrine_DBAL_Driver
{
    
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new Doctrine_DBAL_Driver_PDOConnection(
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
        $dsn = 'mysql:';
        if (isset($params['host'])) {
            $dsn .= 'host=' . $params['host'] . ';';
        }
        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }
        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }
        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }
        
        return $dsn;
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
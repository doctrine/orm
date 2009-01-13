<?php

#namespace Doctrine\DBAL\Driver\PDOMySql;

#use Doctrine\DBAL\Driver;

class Doctrine_DBAL_Driver_PDOMySql_Driver implements Doctrine_DBAL_Driver
{
    /**
     * Attempts to establish a connection with the underlying driver.
     *
     * @param array $params
     * @param string $username
     * @param string $password
     * @param array $driverOptions
     * @return Doctrine\DBAL\Driver\Connection
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        $conn = new Doctrine_DBAL_Driver_PDOConnection(
                $this->_constructPdoDsn($params),
                $username,
                $password,
                $driverOptions);
        $conn->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
        return $conn;
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
        return new Doctrine_DBAL_Platforms_MySqlPlatform();
    }
    
    public function getSchemaManager(Doctrine_DBAL_Connection $conn)
    {
        return new Doctrine_DBAL_Schema_MySqlSchemaManager($conn);
    }
    
}


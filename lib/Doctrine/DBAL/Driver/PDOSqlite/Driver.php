<?php

#namespace Doctrine::DBAL::Driver::PDOOracle;

#use Doctrine::DBAL::Driver;

/**
 * The PDO Sqlite driver.
 *
 * @since 2.0
 */
class Doctrine_DBAL_Driver_PDOSqlite_Driver implements Doctrine_DBAL_Driver
{
    /**
     * Tries to establish a database connection to SQLite.
     *
     * @param array $params
     * @param unknown_type $username
     * @param unknown_type $password
     * @param array $driverOptions
     * @return unknown
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new Doctrine_DBAL_Driver_PDOConnection(
                $this->_constructPdoDsn($params),
                $username,
                $password,
                $driverOptions);
    }
    
    /**
     * Constructs the Sqlite PDO DSN.
     *
     * @return string  The DSN.
     * @override
     */
    protected function _constructPdoDsn(array $params)
    {
        $dsn = 'sqlite:';
        if (isset($params['path'])) {
            $dsn .= $params['path'];
        } else if (isset($params['memory'])) {
            $dsn .= ':memory:';
        }
        
        return $dsn;
    }
    
    /**
     * Gets the database platform that is relevant for this driver.
     */
    public function getDatabasePlatform()
    {
        return new Doctrine_DBAL_Platforms_SqlitePlatform();
    }
    
    /**
     * Gets the schema manager that is relevant for this driver.
     *
     * @param Doctrine\DBAL\Connection $conn
     * @return Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getSchemaManager(Doctrine_DBAL_Connection $conn)
    {
        return new Doctrine_DBAL_Schema_SqliteSchemaManager($conn);
    }
    
}

?>
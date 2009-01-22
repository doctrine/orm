<?php

namespace Doctrine\DBAL\Driver\PDOSqlite;

#use Doctrine::DBAL::Driver;

/**
 * The PDO Sqlite driver.
 *
 * @since 2.0
 */
class Driver implements \Doctrine\DBAL\Driver
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
        return new \Doctrine\DBAL\Driver\PDOConnection(
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
        return new \Doctrine\DBAL\Platforms\SqlitePlatform();
    }
    
    /**
     * Gets the schema manager that is relevant for this driver.
     *
     * @param Doctrine\DBAL\Connection $conn
     * @return Doctrine\DBAL\Schema\SqliteSchemaManager
     */
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new \Doctrine\DBAL\Schema\SqliteSchemaManager($conn);
    }
    
}

?>
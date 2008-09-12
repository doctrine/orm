<?php

#namespace Doctrine::DBAL::Driver::PDOOracle;

#use Doctrine::DBAL::Driver;

class Doctrine_DBAL_Driver_PDOSqlite_Driver implements Doctrine_DBAL_Driver
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
    
    public function getDatabasePlatform()
    {
        return new Doctrine_DBAL_Platforms_SqlitePlatform();
    }
    
    public function getSchemaManager(Doctrine_DBAL_Connection $conn)
    {
        return new Doctrine_DBAL_Schema_SqliteSchemaManager($conn);
    }
    
}

?>
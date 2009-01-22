<?php

namespace Doctrine\Tests\Mocks;


// THIS FILE DOES NOT EXIST YET!!!!
//require_once 'lib/mocks/Doctrine_SchemaManagerMock.php';

class DriverMock implements \Doctrine\DBAL\Driver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new DriverConnectionMock();
    }
    
    /**
     * Constructs the Sqlite PDO DSN.
     *
     * @return string  The DSN.
     * @override
     */
    protected function _constructPdoDsn(array $params)
    {
        return "";
    }
    
    public function getDatabasePlatform()
    {
        return new DatabasePlatformMock();
    }
    
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new SchemaManagerMock($conn);
    }
}


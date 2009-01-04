<?php

#namespace Doctrine\Tests\Mocks;

require_once 'lib/mocks/Doctrine_DriverConnectionMock.php';
require_once 'lib/mocks/Doctrine_DatabasePlatformMock.php';

// THIS FILE DOES NOT EXIST YET!!!!
//require_once 'lib/mocks/Doctrine_SchemaManagerMock.php';

class Doctrine_DriverMock implements Doctrine_DBAL_Driver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new Doctrine_DriverConnectionMock();
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
        return new Doctrine_DatabasePlatformMock();
    }
    
    public function getSchemaManager(Doctrine_DBAL_Connection $conn)
    {
        return new Doctrine_SchemaManagerMock($conn);
    }
}

?>
<?php

namespace Doctrine\Tests\Mocks;


class DriverMock implements \Doctrine\DBAL\Driver
{
    private $_platformMock;

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

    /**
     * @override
     */
    public function getDatabasePlatform()
    {
        if ( ! $this->_platformMock) {
            $this->_platformMock = new DatabasePlatformMock;
        }
        return $this->_platformMock;
    }

    /**
     * @override
     */
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new SchemaManagerMock($conn);
    }

    /* MOCK API */

    public function setDatabasePlatform(\Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        $this->_platformMock = $platform;
    }
}


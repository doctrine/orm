<?php

namespace Doctrine\Tests\Mocks;


class DriverMock implements \Doctrine\DBAL\Driver
{
    private $_platformMock;

    private $_schemaManagerMock;

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
        if($this->_schemaManagerMock == null) {
            return new SchemaManagerMock($conn);
        } else {
            return $this->_schemaManagerMock;
        }
    }

    /* MOCK API */

    public function setDatabasePlatform(\Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        $this->_platformMock = $platform;
    }

    public function setSchemaManager(\Doctrine\DBAL\Schema\AbstractSchemaManager $sm)
    {
        $this->_schemaManagerMock = $sm;
    }

    public function getName()
    {
        return 'mock';
    }

    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        return;
    }
}
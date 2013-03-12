<?php

namespace Doctrine\Tests\Mocks;

/**
 * Mock class for Driver.
 */
class DriverMock implements \Doctrine\DBAL\Driver
{
    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform|null
     */
    private $_platformMock;

    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager|null
     */
    private $_schemaManagerMock;

    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new DriverConnectionMock();
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        if ( ! $this->_platformMock) {
            $this->_platformMock = new DatabasePlatformMock;
        }
        return $this->_platformMock;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        if ($this->_schemaManagerMock == null) {
            return new SchemaManagerMock($conn);
        } else {
            return $this->_schemaManagerMock;
        }
    }

    /* MOCK API */

    /**
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return void
     */
    public function setDatabasePlatform(\Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        $this->_platformMock = $platform;
    }

    /**
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager $sm
     *
     * @return void
     */
    public function setSchemaManager(\Doctrine\DBAL\Schema\AbstractSchemaManager $sm)
    {
        $this->_schemaManagerMock = $sm;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mock';
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        return;
    }
}

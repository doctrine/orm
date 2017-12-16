<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Mock class for Driver.
 */
class DriverMock implements Driver
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
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
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
    public function getSchemaManager(Connection $conn)
    {
        if ($this->_schemaManagerMock == null) {
            return new SchemaManagerMock($conn);
        }

        return $this->_schemaManagerMock;
    }

    /* MOCK API */

    /**
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return void
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->_platformMock = $platform;
    }

    /**
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager $sm
     *
     * @return void
     */
    public function setSchemaManager(AbstractSchemaManager $sm)
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
    public function getDatabase(Connection $conn)
    {
        return;
    }

    public function convertExceptionCode(\Exception $exception)
    {
        return 0;
    }
}

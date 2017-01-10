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
    private $platformMock;

    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager|null
     */
    private $schemaManagerMock;

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
        if ( ! $this->platformMock) {
            $this->platformMock = new DatabasePlatformMock;
        }
        return $this->platformMock;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn)
    {
        if ($this->schemaManagerMock == null) {
            return new SchemaManagerMock($conn);
        } else {
            return $this->schemaManagerMock;
        }
    }

    /* MOCK API */

    /**
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return void
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platformMock = $platform;
    }

    /**
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager $sm
     *
     * @return void
     */
    public function setSchemaManager(AbstractSchemaManager $sm)
    {
        $this->schemaManagerMock = $sm;
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

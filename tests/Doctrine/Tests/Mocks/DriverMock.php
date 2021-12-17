<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use BadMethodCallException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Exception;

/**
 * Mock class for Driver.
 */
class DriverMock implements Driver
{
    /** @var AbstractPlatform|null */
    private $_platformMock;

    /** @var AbstractSchemaManager|null */
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
        if (! $this->_platformMock) {
            $this->_platformMock = new DatabasePlatformMock();
        }

        return $this->_platformMock;
    }

    public function getSchemaManager(Connection $conn, ?AbstractPlatform $platform = null): AbstractSchemaManager
    {
        return $this->_schemaManagerMock ?? new SchemaManagerMock($conn, $platform ?? new DatabasePlatformMock());
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return new ExceptionConverterMock();
    }

    /* MOCK API */

    public function setDatabasePlatform(AbstractPlatform $platform): void
    {
        $this->_platformMock = $platform;
    }

    public function setSchemaManager(AbstractSchemaManager $sm): void
    {
        $this->_schemaManagerMock = $sm;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        throw new BadMethodCallException('Call to deprecated method.');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(Connection $conn)
    {
        return 'not implemented';
    }

    public function convertExceptionCode(Exception $exception): int
    {
        return 0;
    }
}

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

use function sprintf;

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
     * {@inheritDoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        return new DriverConnectionMock();
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function getName()
    {
        throw new BadMethodCallException(sprintf(
            'Call to deprecated method %s().',
            __METHOD__
        ));
    }

    /**
     * {@inheritDoc}
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

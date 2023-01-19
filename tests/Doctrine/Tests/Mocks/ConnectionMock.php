<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use BadMethodCallException;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;

use function is_string;
use function sprintf;

/**
 * Mock class for Connection.
 */
class ConnectionMock extends Connection
{
    /** @var DatabasePlatformMock */
    private $_platformMock;

    /** @var array */
    private $_executeStatements = [];

    /** @var array */
    private $_deletes = [];

    public function __construct(array $params = [], ?Driver $driver = null, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
        $this->_platformMock = new DatabasePlatformMock();

        parent::__construct($params, $driver ?? new DriverMock(), $config, $eventManager);
    }

    public function getDatabase(): string
    {
        return 'mock';
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return $this->_platformMock;
    }

    /**
     * {@inheritdoc}
     */
    public function insert($tableName, array $data, array $types = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($query, array $params = [], array $types = []): int
    {
        throw new BadMethodCallException(sprintf('Call to deprecated method %s().', __METHOD__));
    }

    /**
     * {@inheritdoc}
     */
    public function executeStatement($sql, array $params = [], array $types = []): int
    {
        $this->_executeStatements[] = ['sql' => $sql, 'params' => $params, 'types' => $types];

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($table, array $criteria, array $types = [])
    {
        $this->_deletes[] = ['table' => $table, 'criteria' => $criteria, 'types' => $types];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($statement, array $params = [], $colunm = 0, array $types = [])
    {
        throw new BadMethodCallException('Call to deprecated method.');
    }

    public function query(?string $sql = null): Result
    {
        throw new BadMethodCallException('Call to deprecated method.');
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type = null)
    {
        if (is_string($input)) {
            return "'" . $input . "'";
        }

        return $input;
    }

    public function setDatabasePlatform(AbstractPlatform $platform): void
    {
        $this->_platformMock = $platform;
    }

    /** @return array */
    public function getExecuteStatements(): array
    {
        return $this->_executeStatements;
    }

    /** @return array */
    public function getDeletes(): array
    {
        return $this->_deletes;
    }
}

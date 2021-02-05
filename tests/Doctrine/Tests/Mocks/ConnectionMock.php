<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;

use function is_string;

/**
 * Mock class for Connection.
 */
class ConnectionMock extends Connection
{
    /** @var mixed */
    private $_fetchOneResult;

    /** @var Exception|null */
    private $_fetchOneException;

    /** @var Statement|null */
    private $_queryResult;

    /** @var DatabasePlatformMock */
    private $_platformMock;

    /** @var int */
    private $_lastInsertId = 0;

    /** @var array */
    private $_inserts = [];

    /** @var array */
    private $_executeUpdates = [];

    /**
     * @param array $params
     */
    public function __construct(array $params, Driver $driver, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
        $this->_platformMock = new DatabasePlatformMock();

        parent::__construct($params, $driver, $config, $eventManager);

        // Override possible assignment of platform to database platform mock
        $this->_platform = $this->_platformMock;
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
        $this->_inserts[$tableName][] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($query, array $params = [], array $types = [])
    {
        $this->_executeUpdates[] = ['query' => $query, 'params' => $params, 'types' => $types];
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($seqName = null)
    {
        return $this->_lastInsertId;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($statement, array $params = [], $colnum = 0, array $types = [])
    {
        if ($this->_fetchOneException !== null) {
            throw $this->_fetchOneException;
        }

        return $this->_fetchOneResult;
    }

    public function query(): Statement
    {
        return $this->_queryResult;
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

    /* Mock API */

    /**
     * @param mixed $fetchOneResult
     */
    public function setFetchOneResult($fetchOneResult): void
    {
        $this->_fetchOneResult = $fetchOneResult;
    }

    public function setFetchOneException(?Exception $exception = null): void
    {
        $this->_fetchOneException = $exception;
    }

    public function setDatabasePlatform(AbstractPlatform $platform): void
    {
        $this->_platformMock = $platform;
    }

    public function setLastInsertId(int $id): void
    {
        $this->_lastInsertId = $id;
    }

    public function setQueryResult(Statement $result): void
    {
        $this->_queryResult = $result;
    }

    /**
     * @return array
     */
    public function getInserts(): array
    {
        return $this->_inserts;
    }

    /**
     * @return array
     */
    public function getExecuteUpdates(): array
    {
        return $this->_executeUpdates;
    }

    public function reset(): void
    {
        $this->_inserts      = [];
        $this->_lastInsertId = 0;
    }
}

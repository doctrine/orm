<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use BadMethodCallException;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Exception;

use function is_string;
use function sprintf;

/**
 * Mock class for Connection.
 */
class ConnectionMock extends Connection
{
    /** @var mixed */
    private $_fetchOneResult;

    /** @var Exception|null */
    private $_fetchOneException;

    /** @var Result|null */
    private $_queryResult;

    /** @var DatabasePlatformMock */
    private $_platformMock;

    /** @var int */
    private $_lastInsertId = 0;

    /** @var array */
    private $_inserts = [];

    /** @var array */
    private $_executeStatements = [];

    /** @var array */
    private $_deletes = [];

    public function __construct(array $params = [], ?Driver $driver = null, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
        $this->_platformMock = new DatabasePlatformMock();

        parent::__construct($params, $driver ?? new DriverMock(), $config, $eventManager);

        // Override possible assignment of platform to database platform mock
        $this->_platform = $this->_platformMock;
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
        $this->_inserts[$tableName][] = $data;
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
    public function lastInsertId($seqName = null)
    {
        return $this->_lastInsertId;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne(string $sql, array $params = [], array $types = [])
    {
        if ($this->_fetchOneException !== null) {
            throw $this->_fetchOneException;
        }

        return $this->_fetchOneResult;
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

    public function executeQuery($sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null): Result
    {
        return $this->_queryResult ?? parent::executeQuery($sql, $params, $types, $qcp);
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

    public function setQueryResult(Result $result): void
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
    public function getExecuteStatements(): array
    {
        return $this->_executeStatements;
    }

    /**
     * @return array
     */
    public function getDeletes(): array
    {
        return $this->_deletes;
    }

    public function reset(): void
    {
        $this->_inserts      = [];
        $this->_lastInsertId = 0;
    }
}

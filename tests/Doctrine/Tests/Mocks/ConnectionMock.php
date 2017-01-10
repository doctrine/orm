<?php

namespace Doctrine\Tests\Mocks;
use Doctrine\DBAL\Connection;

/**
 * Mock class for Connection.
 */
class ConnectionMock extends Connection
{
    /**
     * @var mixed
     */
    private $fetchOneResult;

    /**
     * @var DatabasePlatformMock
     */
    private $platformMock;

    /**
     * @var int
     */
    private $lastInsertId = 0;

    /**
     * @var array
     */
    private $inserts = [];

    /**
     * @var array
     */
    private $executeUpdates = [];

    /**
     * @param array                              $params
     * @param \Doctrine\DBAL\Driver              $driver
     * @param \Doctrine\DBAL\Configuration|null  $config
     * @param \Doctrine\Common\EventManager|null $eventManager
     */
    public function __construct(array $params, $driver, $config = null, $eventManager = null)
    {
        $this->platformMock = new DatabasePlatformMock();

        parent::__construct($params, $driver, $config, $eventManager);

        // Override possible assignment of platform to database platform mock
        $this->platform = $this->platformMock;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return $this->platformMock;
    }

    /**
     * {@inheritdoc}
     */
    public function insert($tableName, array $data, array $types = [])
    {
        $this->inserts[$tableName][] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($query, array $params = [], array $types = [])
    {
        $this->executeUpdates[] = ['query' => $query, 'params' => $params, 'types' => $types];
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($seqName = null)
    {
        return $this->lastInsertId;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($statement, array $params = [], $colnum = 0, array $types = [])
    {
        return $this->fetchOneResult;
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
     *
     * @return void
     */
    public function setFetchOneResult($fetchOneResult)
    {
        $this->fetchOneResult = $fetchOneResult;
    }

    /**
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return void
     */
    public function setDatabasePlatform($platform)
    {
        $this->platformMock = $platform;
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function setLastInsertId($id)
    {
        $this->lastInsertId = $id;
    }

    /**
     * @return array
     */
    public function getInserts()
    {
        return $this->inserts;
    }

    /**
     * @return array
     */
    public function getExecuteUpdates()
    {
        return $this->executeUpdates;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->inserts = [];
        $this->lastInsertId = 0;
    }
}

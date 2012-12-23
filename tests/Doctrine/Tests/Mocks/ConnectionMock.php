<?php

namespace Doctrine\Tests\Mocks;

/**
 * Mock class for Connection.
 */
class ConnectionMock extends \Doctrine\DBAL\Connection
{
    /**
     * @var mixed
     */
    private $_fetchOneResult;

    /**
     * @var DatabasePlatformMock
     */
    private $_platformMock;

    /**
     * @var int
     */
    private $_lastInsertId = 0;

    /**
     * @var array
     */
    private $_inserts = array();

    /**
     * @var array
     */
    private $_executeUpdates = array();

    /**
     * @param array                              $params
     * @param \Doctrine\DBAL\Driver              $driver
     * @param \Doctrine\DBAL\Configuration|null  $config
     * @param \Doctrine\Common\EventManager|null $eventManager
     */
    public function __construct(array $params, $driver, $config = null, $eventManager = null)
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
    public function insert($tableName, array $data, array $types = array())
    {
        $this->_inserts[$tableName][] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($query, array $params = array(), array $types = array())
    {
        $this->_executeUpdates[] = array('query' => $query, 'params' => $params, 'types' => $types);
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
    public function fetchColumn($statement, array $params = array(), $colnum = 0)
    {
        return $this->_fetchOneResult;
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
        $this->_fetchOneResult = $fetchOneResult;
    }

    /**
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return void
     */
    public function setDatabasePlatform($platform)
    {
        $this->_platformMock = $platform;
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function setLastInsertId($id)
    {
        $this->_lastInsertId = $id;
    }

    /**
     * @return array
     */
    public function getInserts()
    {
        return $this->_inserts;
    }

    /**
     * @return array
     */
    public function getExecuteUpdates()
    {
        return $this->_executeUpdates;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->_inserts = array();
        $this->_lastInsertId = 0;
    }
}

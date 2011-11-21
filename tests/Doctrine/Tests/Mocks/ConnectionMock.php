<?php

namespace Doctrine\Tests\Mocks;

class ConnectionMock extends \Doctrine\DBAL\Connection
{
    private $_fetchOneResult;
    private $_platformMock;
    private $_lastInsertId = 0;
    private $_inserts = array();
    private $_executeUpdates = array();

    public function __construct(array $params, $driver, $config = null, $eventManager = null)
    {
        $this->_platformMock = new DatabasePlatformMock();

        parent::__construct($params, $driver, $config, $eventManager);

        // Override possible assignment of platform to database platform mock
        $this->_platform = $this->_platformMock;
    }

    /**
     * @override
     */
    public function getDatabasePlatform()
    {
        return $this->_platformMock;
    }

    /**
     * @override
     */
    public function insert($tableName, array $data, array $types = array())
    {
        $this->_inserts[$tableName][] = $data;
    }

    /**
     * @override
     */
    public function executeUpdate($query, array $params = array(), array $types = array())
    {
        $this->_executeUpdates[] = array('query' => $query, 'params' => $params, 'types' => $types);
    }

    /**
     * @override
     */
    public function lastInsertId($seqName = null)
    {
        return $this->_lastInsertId;
    }

    /**
     * @override
     */
    public function fetchColumn($statement, array $params = array(), $colnum = 0)
    {
        return $this->_fetchOneResult;
    }

    /**
     * @override
     */
    public function quote($input, $type = null)
    {
        if (is_string($input)) {
            return "'" . $input . "'";
        }
        return $input;
    }

    /* Mock API */

    public function setFetchOneResult($fetchOneResult)
    {
        $this->_fetchOneResult = $fetchOneResult;
    }

    public function setDatabasePlatform($platform)
    {
        $this->_platformMock = $platform;
    }

    public function setLastInsertId($id)
    {
        $this->_lastInsertId = $id;
    }

    public function getInserts()
    {
        return $this->_inserts;
    }

    public function getExecuteUpdates()
    {
        return $this->_executeUpdates;
    }

    public function reset()
    {
        $this->_inserts = array();
        $this->_lastInsertId = 0;
    }
}
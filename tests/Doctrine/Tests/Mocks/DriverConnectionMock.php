<?php

namespace Doctrine\Tests\Mocks;

/**
 * Mock class for DriverConnection.
 */
class DriverConnectionMock implements \Doctrine\DBAL\Driver\Connection
{
    /**
     * @var string
     */
    private $lastPreparedStatementSql = '';

    /**
     * {@inheritdoc}
     */
    public function prepare($prepareString)
    {
        $this->lastPreparedStatementSql = $prepareString;

        return new StatementMock;
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        return new StatementMock;
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type=\PDO::PARAM_STR)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
    }

    /* Mock API */

    /**
     * @return string
     */
    public function getLastPreparedStatementSql()
    {
        return $this->lastPreparedStatementSql;
    }
}

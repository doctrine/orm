<?php

namespace Shitty\Tests\Mocks;

/**
 * Mock class for DriverConnection.
 */
class DriverConnectionMock implements \Shitty\DBAL\Driver\Connection
{
    /**
     * @var \Shitty\DBAL\Driver\Statement
     */
    private $statementMock;

    /**
     * @return \Shitty\DBAL\Driver\Statement
     */
    public function getStatementMock()
    {
        return $this->statementMock;
    }

    /**
     * @param \Shitty\DBAL\Driver\Statement $statementMock
     */
    public function setStatementMock($statementMock)
    {
        $this->statementMock = $statementMock;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($prepareString)
    {
        return $this->statementMock ?: new StatementMock();
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        return $this->statementMock ?: new StatementMock();
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
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * Mock class for DriverConnection.
 */
class DriverConnectionMock implements Connection
{
    /** @var Result|null */
    private $resultMock;

    public function setResultMock(?Result $resultMock): void
    {
        $this->resultMock = $resultMock;
    }

    public function prepare(string $sql): Statement
    {
        return new StatementMock();
    }

    public function query(string $sql): Result
    {
        return $this->resultMock ?? new DriverResultMock();
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value, $type = ParameterType::STRING)
    {
    }

    public function exec(string $sql): int
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

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

    /**
     * {@inheritDoc}
     */
    public function prepare($prepareString): Statement
    {
        return new StatementMock();
    }

    public function query(?string $sql = null): Result
    {
        return $this->resultMock ?? new DriverResultMock();
    }

    /**
     * {@inheritDoc}
     */
    public function quote($input, $type = ParameterType::STRING)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function exec($statement): int
    {
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function errorInfo()
    {
    }
}

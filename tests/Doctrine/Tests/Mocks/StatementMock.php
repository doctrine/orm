<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use IteratorAggregate;

/**
 * This class is a mock of the Statement interface.
 */
class StatementMock implements IteratorAggregate, Statement
{
    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, int $type = ParameterType::STRING) : void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, int $type = ParameterType::STRING, ?int $length = null) : void
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

    /**
     * {@inheritdoc}
     */
    public function execute(?array $params = null) : void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount() : int
    {
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor() : void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount() : int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode(int $fetchMode, ...$args) : void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(?int $fetchMode = null, ...$args)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(?int $fetchMode = null, ...$args) : array
    {
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
    }
}

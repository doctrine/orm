<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use EmptyIterator;
use IteratorAggregate;
use PDO;
use Traversable;

/**
 * This class is a mock of the Statement interface.
 */
class StatementMock implements IteratorAggregate, Statement
{
    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = null, $length = null)
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
    public function execute($params = null): Result
    {
        return new DriverResultMock();
    }

    public function rowCount(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($fetchStyle, $arg2 = null, $arg3 = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($fetchMode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
    }

    public function getIterator(): Traversable
    {
        return new EmptyIterator();
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use EmptyIterator;
use IteratorAggregate;
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
    public function execute($params = null): Result
    {
        return new DriverResultMock();
    }

    public function getIterator(): Traversable
    {
        return new EmptyIterator();
    }
}

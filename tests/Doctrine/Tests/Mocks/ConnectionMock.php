<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use BadMethodCallException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

use function is_string;
use function sprintf;

/**
 * Mock class for Connection.
 */
class ConnectionMock extends Connection
{
    public function getDatabase(): string
    {
        return 'mock';
    }

    /**
     * {@inheritdoc}
     */
    public function insert($tableName, array $data, array $types = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($query, array $params = [], array $types = []): int
    {
        throw new BadMethodCallException(sprintf('Call to deprecated method %s().', __METHOD__));
    }

    public function query(?string $sql = null): Result
    {
        throw new BadMethodCallException('Call to deprecated method.');
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
}

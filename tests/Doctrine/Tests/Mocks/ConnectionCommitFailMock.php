<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;

/**
 * Mock class for Connection.
 */
class ConnectionCommitFailMock extends ConnectionMock
{
    /**
     * Return false to raise an error
     *
     * @return bool
     */
    public function commit()
    {
        return false;
    }
}

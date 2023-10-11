<?php

declare(strict_types=1);

namespace Doctrine\Performance;

use Doctrine\DBAL\Cache\ArrayResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\SQLite\Driver;
use Doctrine\DBAL\Result;

final class ArrayResultFactory
{
    public static function createFromArray(array $resultSet): Result
    {
        return new Result(new ArrayResult($resultSet), new Connection([], new Driver()));
    }
}

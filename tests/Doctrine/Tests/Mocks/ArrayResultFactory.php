<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

use function interface_exists;

final class ArrayResultFactory
{
    public static function createFromArray(array $resultSet): Result
    {
        if (interface_exists(Result::class)) {
            // DBAL 2 compatibility.
            return new ResultMock($resultSet);
        }

        return new Result(new DriverResultMock($resultSet), new Connection([], new DriverMock()));
    }
}

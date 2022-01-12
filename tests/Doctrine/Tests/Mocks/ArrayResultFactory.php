<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

final class ArrayResultFactory
{
    public static function createFromArray(array $resultSet): Result
    {
        return new Result(new DriverResultMock($resultSet), new Connection([], new DriverMock()));
    }
}

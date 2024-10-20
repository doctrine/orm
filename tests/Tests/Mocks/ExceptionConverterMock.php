<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Query;

class ExceptionConverterMock implements ExceptionConverter
{
    public function convert(Exception $exception, Query|null $query): DriverException
    {
        return new DriverException($exception, $query);
    }
}

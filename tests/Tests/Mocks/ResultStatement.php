<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Driver\ResultStatement as DriverResultStatement;
use IteratorAggregate;

use function interface_exists;

if (interface_exists(DriverResultStatement::class)) {
    interface ResultStatement extends DriverResultStatement, IteratorAggregate
    {
    }
} else {
    interface ResultStatement
    {
    }
}

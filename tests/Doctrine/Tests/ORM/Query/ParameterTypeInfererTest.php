<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\Tests\OrmTestCase;

class ParameterTypeInfererTest extends OrmTestCase
{
    /** @psalm-return list<array{mixed, int|string}> */
    public function providerParameterTypeInferer(): array
    {
        return [
            [1,                 Types::INTEGER],
            ['bar',             ParameterType::STRING],
            ['1',               ParameterType::STRING],
            [new DateTime(),     Types::DATETIME_MUTABLE],
            [new DateTimeImmutable(), Types::DATETIME_IMMUTABLE],
            [new DateInterval('P1D'), Types::DATEINTERVAL],
            [[2],          Connection::PARAM_INT_ARRAY],
            [['foo'],      Connection::PARAM_STR_ARRAY],
            [['1','2'],    Connection::PARAM_STR_ARRAY],
            [[],           Connection::PARAM_STR_ARRAY],
            [true,              Types::BOOLEAN],
        ];
    }

    /**
     * @param mixed      $value
     * @param int|string $expected
     *
     * @dataProvider providerParameterTypeInferer
     */
    public function testParameterTypeInferer($value, $expected): void
    {
        self::assertEquals($expected, ParameterTypeInferer::inferType($value));
    }
}

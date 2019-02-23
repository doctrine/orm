<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\Tests\OrmTestCase;
use const PHP_VERSION_ID;

class ParameterTypeInfererTest extends OrmTestCase
{
    public function providerParameterTypeInferer()
    {
        $data = [
            [1,                 Type::INTEGER],
            ['bar',             ParameterType::STRING],
            ['1',               ParameterType::STRING],
            [new DateTime(),     Type::DATETIME],
            [new DateInterval('P1D'), Type::DATEINTERVAL],
            [[2],          Connection::PARAM_INT_ARRAY],
            [['foo'],      Connection::PARAM_STR_ARRAY],
            [['1','2'],    Connection::PARAM_STR_ARRAY],
            [[],           Connection::PARAM_STR_ARRAY],
            [true,              Type::BOOLEAN],
        ];

        if (PHP_VERSION_ID >= 50500) {
            $data[] = [new DateTimeImmutable(), Type::DATETIME];
        }

        return $data;
    }

    /**
     * @dataProvider providerParameterTypeInferer
     */
    public function testParameterTypeInferer($value, $expected) : void
    {
        self::assertEquals($expected, ParameterTypeInferer::inferType($value));
    }
}

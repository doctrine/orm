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
use Doctrine\Tests\Models\Enums\AccessLevel;
use Doctrine\Tests\Models\Enums\UserStatus;
use Doctrine\Tests\OrmTestCase;
use Generator;

use const PHP_VERSION_ID;

class ParameterTypeInfererTest extends OrmTestCase
{
    /** @psalm-return Generator<string, array{mixed, (int|string)}> */
    public static function providerParameterTypeInferer(): Generator
    {
        yield 'integer' => [1, Types::INTEGER];
        yield 'string' => ['bar', ParameterType::STRING];
        yield 'numeric_string' => ['1', ParameterType::STRING];
        yield 'datetime_object' => [new DateTime(), Types::DATETIME_MUTABLE];
        yield 'datetime_immutable_object' => [new DateTimeImmutable(), Types::DATETIME_IMMUTABLE];
        yield 'date_interval_object' => [new DateInterval('P1D'), Types::DATEINTERVAL];
        yield 'array_of_int' => [[2], Connection::PARAM_INT_ARRAY];
        yield 'array_of_string' => [['foo'], Connection::PARAM_STR_ARRAY];
        yield 'array_of_numeric_string' => [['1', '2'], Connection::PARAM_STR_ARRAY];
        yield 'empty_array' => [[], Connection::PARAM_STR_ARRAY];
        yield 'boolean' => [true, Types::BOOLEAN];

        if (PHP_VERSION_ID >= 80100) {
            yield 'int_backed_enum' => [AccessLevel::Admin, Types::INTEGER];
            yield 'string_backed_enum' => [UserStatus::Active, Types::STRING];
            yield 'array_of_int_backed_enum' => [[AccessLevel::Admin], Connection::PARAM_INT_ARRAY];
            yield 'array_of_string_backed_enum' => [[UserStatus::Active], Connection::PARAM_STR_ARRAY];
        }
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

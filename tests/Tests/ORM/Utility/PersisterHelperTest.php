<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Utility\PersisterHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PersisterHelperTest extends TestCase
{
    /** @param ArrayParameterType::* $expected */
    #[DataProvider('provideBindingTypes')]
    public function testGetArrayBindingType(ArrayParameterType $expected, ParameterType|string $type): void
    {
        self::assertSame($expected, PersisterHelper::getArrayBindingType($type));
    }

    /** @return iterable<string, array{ArrayParameterType::*, ParameterType|string}> */
    public static function provideBindingTypes(): iterable
    {
        yield 'string' => [ArrayParameterType::STRING, ParameterType::STRING];
        yield 'integer' => [ArrayParameterType::INTEGER, ParameterType::INTEGER];
        yield 'ascii' => [ArrayParameterType::ASCII, ParameterType::ASCII];
        yield 'binary' => [ArrayParameterType::BINARY, ParameterType::BINARY];
        yield 'boolean' => [ArrayParameterType::INTEGER, ParameterType::BOOLEAN];
        yield 'large object' => [ArrayParameterType::BINARY, ParameterType::LARGE_OBJECT];
        yield 'null' => [ArrayParameterType::STRING, ParameterType::NULL];

        yield 'type name' => [ArrayParameterType::STRING, Types::STRING];
        yield 'boolean type name' => [ArrayParameterType::INTEGER, Types::BOOLEAN];
        yield 'blob type name' => [ArrayParameterType::BINARY, Types::BLOB];
    }
}

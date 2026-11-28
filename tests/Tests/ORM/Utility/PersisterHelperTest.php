<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Internal\TypeProviderLocator;
use Doctrine\ORM\Utility\PersisterHelper;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PersisterHelperTest extends OrmTestCase
{
    /** @param ArrayParameterType::* $expected */
    #[DataProvider('provideBindingTypes')]
    public function testGetArrayBindingType(ArrayParameterType|int $expected, ParameterType|int|string $type): void
    {
        self::assertSame($expected, PersisterHelper::getArrayBindingType(
            $type,
            TypeProviderLocator::fromConnection($this->getTestEntityManager()->getConnection()),
        ));
    }

    /** @return iterable<string, array{ArrayParameterType::*, ParameterType|int|string}> */
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

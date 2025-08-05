<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\JoinTableMapping;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class ManyToManyOwningSideMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new ManyToManyOwningSideMapping(
            fieldName: 'foo',
            sourceEntity: self::class,
            targetEntity: self::class,
        );

        $mapping->joinTable                  = new JoinTableMapping('bar');
        $mapping->joinTableColumns           = ['foo', 'bar'];
        $mapping->relationToSourceKeyColumns = ['foo' => 'bar'];
        $mapping->relationToTargetKeyColumns = ['bar' => 'baz'];

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof ManyToManyOwningSideMapping);

        self::assertSame($resurrectedMapping->joinTable->name, 'bar');
        self::assertSame(['foo', 'bar'], $resurrectedMapping->joinTableColumns);
        self::assertSame(['foo' => 'bar'], $resurrectedMapping->relationToSourceKeyColumns);
        self::assertSame(['bar' => 'baz'], $resurrectedMapping->relationToTargetKeyColumns);
    }

    #[DataProvider('mappingsProvider')]
    public function testNullableDefaults(bool $expectedValue, ManyToManyOwningSideMapping $mapping): void
    {
        foreach ($mapping->joinTable->joinColumns as $joinColumn) {
            self::assertSame($expectedValue, $joinColumn->nullable);
        }
    }

    /** @return iterable<string, array{bool, ManyToManyOwningSideMapping}> */
    public static function mappingsProvider(): iterable
    {
        $namingStrategy = new DefaultNamingStrategy();

        yield 'defaults to false' => [
            false,
            ManyToManyOwningSideMapping::fromMappingArrayAndNamingStrategy([
                'fieldName' => 'foo',
                'sourceEntity' => self::class,
                'targetEntity' => self::class,
                'isOwningSide' => true,
                'joinTable' => [
                    'name' => 'bar',
                    'joinColumns' => [
                        ['name' => 'bar_id', 'referencedColumnName' => 'id'],
                    ],
                    'inverseJoinColumns' => [
                        ['name' => 'foo_id', 'referencedColumnName' => 'id'],
                    ],
                ],
            ], $namingStrategy),
        ];

        yield 'explicitly marked as nullable' => [
            true,
            ManyToManyOwningSideMapping::fromMappingArrayAndNamingStrategy([
                'fieldName' => 'foo',
                'sourceEntity' => self::class,
                'targetEntity' => self::class,
                'isOwningSide' => true,
                'joinTable' => [
                    'name' => 'bar',
                    'joinColumns' => [
                        ['name' => 'bar_id', 'referencedColumnName' => 'id', 'nullable' => true],
                    ],
                    'inverseJoinColumns' => [
                        ['name' => 'foo_id', 'referencedColumnName' => 'id', 'nullable' => true],
                    ],
                ],
                'id' => true,
            ], $namingStrategy),
        ];
    }
}

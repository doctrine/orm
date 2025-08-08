<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\OneToOneOwningSideMapping;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class OneToOneOwningSideMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new OneToOneOwningSideMapping(
            fieldName: 'foo',
            sourceEntity: self::class,
            targetEntity: self::class,
        );

        $mapping->joinColumns              = [new JoinColumnMapping('foo_id', 'id')];
        $mapping->joinColumnFieldNames     = ['foo' => 'bar'];
        $mapping->sourceToTargetKeyColumns = ['foo' => 'bar'];
        $mapping->targetToSourceKeyColumns = ['bar' => 'foo'];

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof OneToOneOwningSideMapping);

        self::assertCount(1, $resurrectedMapping->joinColumns);
        self::assertSame(['foo' => 'bar'], $resurrectedMapping->joinColumnFieldNames);
        self::assertSame(['foo' => 'bar'], $resurrectedMapping->sourceToTargetKeyColumns);
        self::assertSame(['bar' => 'foo'], $resurrectedMapping->targetToSourceKeyColumns);
    }

    #[DataProvider('mappingsProvider')]
    public function testNullableDefaults(bool $expectedValue, OneToOneOwningSideMapping $mapping): void
    {
        foreach ($mapping->joinColumns as $joinColumn) {
            self::assertSame($expectedValue, $joinColumn->nullable);
        }
    }

    /** @return iterable<string, array{bool, OneToOneOwningSideMapping}> */
    public static function mappingsProvider(): iterable
    {
        $namingStrategy = new DefaultNamingStrategy();

        yield 'not part of the identifier' => [
            true,
            OneToOneOwningSideMapping::fromMappingArrayAndName([
                'fieldName' => 'foo',
                'sourceEntity' => self::class,
                'targetEntity' => self::class,
                'isOwningSide' => true,
                'joinColumns' => [
                    ['name' => 'foo_id', 'referencedColumnName' => 'id'],
                ],
                'id' => false,
            ], $namingStrategy, self::class, null, false),
        ];

        yield 'part of the identifier' => [
            false,
            OneToOneOwningSideMapping::fromMappingArrayAndName([
                'fieldName' => 'foo',
                'sourceEntity' => self::class,
                'targetEntity' => self::class,
                'isOwningSide' => true,
                'joinColumns' => [
                    ['name' => 'foo_id', 'referencedColumnName' => 'id'],
                ],
                'id' => true,
            ], $namingStrategy, self::class, null, false),
        ];

        yield 'part of the identifier, but explicitly marked as nullable' => [
            false, // user's intent ignored at the ORM level
            OneToOneOwningSideMapping::fromMappingArrayAndName([
                'fieldName' => 'foo',
                'sourceEntity' => self::class,
                'targetEntity' => self::class,
                'isOwningSide' => true,
                'joinColumns' => [
                    ['name' => 'foo_id', 'referencedColumnName' => 'id', 'nullable' => true],
                ],
                'id' => true,
            ], $namingStrategy, self::class, null, false),
        ];
    }
}

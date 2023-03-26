<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class AssociationMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new MyAssociationMapping(
            fieldName: 'foo',
            sourceEntity: self::class,
            targetEntity: self::class,
        );

        $mapping->mappedBy             = 'foo';
        $mapping->inversedBy           = 'bar';
        $mapping->cascade              = ['persist'];
        $mapping->fetch                = ClassMetadata::FETCH_EAGER;
        $mapping->inherited            = self::class;
        $mapping->declared             = self::class;
        $mapping->cache                = ['usage' => ClassMetadata::CACHE_USAGE_READ_ONLY];
        $mapping->id                   = true;
        $mapping->isOnDeleteCascade    = true;
        $mapping->joinColumnFieldNames = ['foo' => 'bar'];
        $mapping->joinTableColumns     = ['foo', 'bar'];
        $mapping->originalClass        = self::class;
        $mapping->originalField        = 'foo';
        $mapping->orphanRemoval        = true;
        $mapping->unique               = true;

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof AssociationMapping);

        self::assertSame($resurrectedMapping->mappedBy, 'foo');
        self::assertSame($resurrectedMapping->inversedBy, 'bar');
        self::assertSame($resurrectedMapping->cascade, ['persist']);
        self::assertSame($resurrectedMapping->fetch, ClassMetadata::FETCH_EAGER);
        self::assertSame($resurrectedMapping->inherited, self::class);
        self::assertSame($resurrectedMapping->declared, self::class);
        self::assertSame($resurrectedMapping->cache, ['usage' => ClassMetadata::CACHE_USAGE_READ_ONLY]);
        self::assertSame($resurrectedMapping->id, true);
        self::assertSame($resurrectedMapping->isOnDeleteCascade, true);
        self::assertSame($resurrectedMapping->joinColumnFieldNames, ['foo' => 'bar']);
        self::assertSame($resurrectedMapping->joinTableColumns, ['foo', 'bar']);
        self::assertSame($resurrectedMapping->originalClass, self::class);
        self::assertSame($resurrectedMapping->originalField, 'foo');
        self::assertSame($resurrectedMapping->orphanRemoval, true);
        self::assertSame($resurrectedMapping->unique, true);
    }
}

class MyAssociationMapping extends AssociationMapping
{
}

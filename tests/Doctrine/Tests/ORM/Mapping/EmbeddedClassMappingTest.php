<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\EmbeddedClassMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class EmbeddedClassMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping                = new EmbeddedClassMapping(self::class);
        $mapping->columnPrefix  = 'these';
        $mapping->declaredField = 'values';
        $mapping->originalField = 'make';
        $mapping->inherited     = self::class; // no
        $mapping->declared      = self::class; // sense

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof EmbeddedClassMapping);

        self::assertSame($resurrectedMapping->columnPrefix, 'these');
        self::assertSame($resurrectedMapping->declaredField, 'values');
        self::assertSame($resurrectedMapping->originalField, 'make');
        self::assertSame($resurrectedMapping->inherited, self::class);
        self::assertSame($resurrectedMapping->declared, self::class);
    }
}

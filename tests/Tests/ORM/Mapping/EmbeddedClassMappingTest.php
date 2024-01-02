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

        self::assertSame('these', $resurrectedMapping->columnPrefix);
        self::assertSame('values', $resurrectedMapping->declaredField);
        self::assertSame('make', $resurrectedMapping->originalField);
        self::assertSame(self::class, $resurrectedMapping->inherited);
        self::assertSame(self::class, $resurrectedMapping->declared);
    }
}

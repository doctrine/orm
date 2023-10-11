<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\JoinColumnMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class JoinColumnMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new JoinColumnMapping('foo', 'id');

        $mapping->unique               = true;
        $mapping->quoted               = true;
        $mapping->fieldName            = 'bar';
        $mapping->onDelete             = 'CASCADE';
        $mapping->columnDefinition     = 'VARCHAR(255)';
        $mapping->nullable             = true;
        $mapping->referencedColumnName = 'baz';
        $mapping->options              = ['foo' => 'bar'];

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof JoinColumnMapping);

        self::assertSame('foo', $resurrectedMapping->name);
        self::assertTrue($resurrectedMapping->unique);
        self::assertTrue($resurrectedMapping->quoted);
        self::assertSame('bar', $resurrectedMapping->fieldName);
        self::assertSame('CASCADE', $resurrectedMapping->onDelete);
        self::assertSame('VARCHAR(255)', $resurrectedMapping->columnDefinition);
        self::assertTrue($resurrectedMapping->nullable);
        self::assertSame('baz', $resurrectedMapping->referencedColumnName);
        self::assertSame(['foo' => 'bar'], $resurrectedMapping->options);
    }
}

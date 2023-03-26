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
        $mapping = new JoinColumnMapping();

        $mapping->name                 = 'foo';
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

        self::assertSame($resurrectedMapping->name, 'foo');
        self::assertTrue($resurrectedMapping->unique);
        self::assertTrue($resurrectedMapping->quoted);
        self::assertSame($resurrectedMapping->fieldName, 'bar');
        self::assertSame($resurrectedMapping->onDelete, 'CASCADE');
        self::assertSame($resurrectedMapping->columnDefinition, 'VARCHAR(255)');
        self::assertTrue($resurrectedMapping->nullable);
        self::assertSame($resurrectedMapping->referencedColumnName, 'baz');
        self::assertSame($resurrectedMapping->options, ['foo' => 'bar']);
    }
}

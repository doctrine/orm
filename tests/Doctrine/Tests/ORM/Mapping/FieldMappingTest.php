<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class FieldMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new FieldMapping(
            type: 'string',
            fieldName: 'id',
            columnName: 'id',
        );

        $mapping->length           = 255;
        $mapping->id               = true;
        $mapping->nullable         = true;
        $mapping->notInsertable    = true;
        $mapping->notUpdatable     = true;
        $mapping->columnDefinition = 'VARCHAR(255)';
        $mapping->generated        = ClassMetadata::GENERATOR_TYPE_AUTO;
        $mapping->enumType         = 'MyEnum';
        $mapping->precision        = 10;
        $mapping->scale            = 2;
        $mapping->unique           = true;
        $mapping->inherited        = self::class;
        $mapping->originalClass    = self::class;
        $mapping->originalField    = 'id';
        $mapping->quoted           = true;
        $mapping->declared         = self::class;
        $mapping->declaredField    = 'id';
        $mapping->options          = ['foo' => 'bar'];
        $mapping->version          = true;
        $mapping->default          = 'foo';

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof FieldMapping);

        self::assertSame($resurrectedMapping->length, 255);
        self::assertTrue($resurrectedMapping->id);
        self::assertTrue($resurrectedMapping->nullable);
        self::assertTrue($resurrectedMapping->notInsertable);
        self::assertTrue($resurrectedMapping->notUpdatable);
        self::assertSame($resurrectedMapping->columnDefinition, 'VARCHAR(255)');
        self::assertSame($resurrectedMapping->generated, ClassMetadata::GENERATOR_TYPE_AUTO);
        self::assertSame($resurrectedMapping->enumType, 'MyEnum');
        self::assertSame($resurrectedMapping->precision, 10);
        self::assertSame($resurrectedMapping->scale, 2);
        self::assertTrue($resurrectedMapping->unique);
        self::assertSame($resurrectedMapping->inherited, self::class);
        self::assertSame($resurrectedMapping->originalClass, self::class);
        self::assertSame($resurrectedMapping->originalField, 'id');
        self::assertTrue($resurrectedMapping->quoted);
        self::assertSame($resurrectedMapping->declared, self::class);
        self::assertSame($resurrectedMapping->declaredField, 'id');
        self::assertSame($resurrectedMapping->options, ['foo' => 'bar']);
        self::assertTrue($resurrectedMapping->version);
        self::assertSame($resurrectedMapping->default, 'foo');
    }
}

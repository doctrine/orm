<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ReflectionReadonlyProperty;
use Doctrine\Tests\Models\CMS\CmsTag;
use Doctrine\Tests\Models\ReadonlyProperties\Author;
use Doctrine\Tests\Models\ReadonlyProperties\Library;
use Doctrine\Tests\Models\ValueObjects\Uuid;
use Generator;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @requires PHP 8.1
 */
class ReflectionReadonlyPropertyTest extends TestCase
{
    /**
     * @dataProvider sameValueProvider
     */
    public function testSecondWriteWithSameValue(object $entity, string $property, mixed $value, mixed $sameValue): void
    {
        $wrappedReflection = new ReflectionProperty($entity, $property);
        $reflection        = new ReflectionReadonlyProperty($wrappedReflection);

        $reflection->setValue($entity, $value);

        self::assertSame($value, $wrappedReflection->getValue($entity));
        self::assertSame($value, $reflection->getValue($entity));

        $reflection->setValue($entity, $sameValue);

        /*
         * Intentionally testing against the initial $value rather than the $sameValue that we just set above one in
         * order to catch false positives when dealing with object types
         */
        self::assertSame($value, $wrappedReflection->getValue($entity));
        self::assertSame($value, $reflection->getValue($entity));
    }

    public function sameValueProvider(): Generator
    {
        yield 'string' => [
            'entity' => new Author(),
            'property' => 'name',
            'value' => 'John Doe',
            'sameValue' => 'John Doe',
        ];

        yield 'uuid' => [
            'entity' => new Library(),
            'property' => 'uuid',
            'value' => new Uuid('438d5dc3-36c9-410a-88db-7a184856ebb8'),
            'sameValue' => new Uuid('438d5dc3-36c9-410a-88db-7a184856ebb8'),
        ];
    }

    /**
     * @dataProvider differentValueProvider
     */
    public function testSecondWriteWithDifferentValue(
        object $entity,
        string $property,
        mixed $value,
        mixed $differentValue,
        string $expectedExceptionMessage
    ): void {
        $wrappedReflection = new ReflectionProperty($entity, $property);
        $reflection        = new ReflectionReadonlyProperty($wrappedReflection);

        $reflection->setValue($entity, $value);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $reflection->setValue($entity, $differentValue);
    }

    public function differentValueProvider(): Generator
    {
        yield 'string' => [
            'entity' => new Author(),
            'property' => 'name',
            'value' => 'John Doe',
            'differentValue' => 'Jane Doe',
            'expectedExceptionMessage' => 'Attempting to change readonly property Doctrine\Tests\Models\ReadonlyProperties\Author::$name.',
        ];

        yield 'uuid' => [
            'entity' => new Library(),
            'property' => 'uuid',
            'value' => new Uuid('438d5dc3-36c9-410a-88db-7a184856ebb8'),
            'differentValue' => new Uuid('5d5049ee-01fd-4b66-9f82-9f637fff6a7d'),
            'expectedExceptionMessage' => 'Attempting to change readonly property Doctrine\Tests\Models\ReadonlyProperties\Library::$uuid.',
        ];
    }

    public function testNonReadonlyPropertiesAreForbidden(): void
    {
        $reflection = new ReflectionProperty(CmsTag::class, 'name');

        $this->expectException(InvalidArgumentException::class);
        new ReflectionReadonlyProperty($reflection);
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ReflectionReadonlyProperty;
use Doctrine\Tests\Models\CMS\CmsTag;
use Doctrine\Tests\Models\ReadonlyProperties\Author;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ReflectionReadonlyPropertyTest extends TestCase
{
    public function testSecondWriteWithSameValue(): void
    {
        $author = new Author();

        $wrappedReflection = new ReflectionProperty($author, 'name');
        $reflection        = new ReflectionReadonlyProperty($wrappedReflection);

        $reflection->setValue($author, 'John Doe');

        self::assertSame('John Doe', $wrappedReflection->getValue($author));
        self::assertSame('John Doe', $reflection->getValue($author));

        $reflection->setValue($author, 'John Doe');

        self::assertSame('John Doe', $wrappedReflection->getValue($author));
        self::assertSame('John Doe', $reflection->getValue($author));
    }

    public function testSecondWriteWithDifferentValue(): void
    {
        $author = new Author();

        $wrappedReflection = new ReflectionProperty($author, 'name');
        $reflection        = new ReflectionReadonlyProperty($wrappedReflection);

        $reflection->setValue($author, 'John Doe');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Attempting to change readonly property Doctrine\Tests\Models\ReadonlyProperties\Author::$name.');
        $reflection->setValue($author, 'Jane Doe');
    }

    public function testNonReadonlyPropertiesAreForbidden(): void
    {
        $reflection = new ReflectionProperty(CmsTag::class, 'name');

        $this->expectException(InvalidArgumentException::class);
        new ReflectionReadonlyProperty($reflection);
    }
}

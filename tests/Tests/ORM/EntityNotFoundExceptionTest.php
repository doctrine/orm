<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see \Doctrine\ORM\EntityNotFoundException}
 */
#[CoversClass(EntityNotFoundException::class)]
class EntityNotFoundExceptionTest extends TestCase
{
    public function testFromClassNameAndIdentifier(): void
    {
        $exception = EntityNotFoundException::fromClassNameAndIdentifier(
            'foo',
            ['foo' => 'bar'],
        );

        self::assertInstanceOf(EntityNotFoundException::class, $exception);
        self::assertSame('Entity of type \'foo\' for IDs foo(bar) was not found', $exception->getMessage());

        $exception = EntityNotFoundException::fromClassNameAndIdentifier(
            'foo',
            [],
        );

        self::assertInstanceOf(EntityNotFoundException::class, $exception);
        self::assertSame('Entity of type \'foo\' was not found', $exception->getMessage());
    }

    public function testNoIdentifierFound(): void
    {
        $exception = EntityNotFoundException::noIdentifierFound('foo');

        self::assertInstanceOf(EntityNotFoundException::class, $exception);
        self::assertSame('Unable to find "foo" entity identifier associated with the UnitOfWork', $exception->getMessage());
    }
}

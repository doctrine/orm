<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\Exception\EntityNotFound;
use Doctrine\Tests\DoctrineTestCase;

/**
 * Tests for {@see \Doctrine\ORM\Exception\EntityNotFoundException}
 * @covers \Doctrine\ORM\Exception\EntityNotFound
 */
class EntityNotFoundExceptionTest extends DoctrineTestCase
{
    public function testFromClassNameAndIdentifier() : void
    {
        $exception = EntityNotFound::fromClassNameAndIdentifier(
            'foo',
            ['foo' => 'bar']
        );

        self::assertInstanceOf(EntityNotFound::class, $exception);
        self::assertSame('Entity of type \'foo\' for IDs foo(bar) was not found', $exception->getMessage());

        $exception = EntityNotFound::fromClassNameAndIdentifier(
            'foo',
            []
        );

        self::assertInstanceOf(EntityNotFound::class, $exception);
        self::assertSame('Entity of type \'foo\' was not found', $exception->getMessage());
    }
}

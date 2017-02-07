<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Tests\DoctrineTestCase;

/**
 * Tests for {@see \Doctrine\ORM\EntityNotFoundException}
 *
 * @covers \Doctrine\ORM\EntityNotFoundException
 */
class EntityNotFoundExceptionTest extends DoctrineTestCase
{
    public function testFromClassNameAndIdentifier()
    {
        $exception = EntityNotFoundException::fromClassNameAndIdentifier(
            'foo',
            ['foo' => 'bar']
        );

        self::assertInstanceOf(EntityNotFoundException::class, $exception);
        self::assertSame('Entity of type \'foo\' for IDs foo(bar) was not found', $exception->getMessage());

        $exception = EntityNotFoundException::fromClassNameAndIdentifier(
            'foo',
            []
        );

        self::assertInstanceOf(EntityNotFoundException::class, $exception);
        self::assertSame('Entity of type \'foo\' was not found', $exception->getMessage());
    }
}

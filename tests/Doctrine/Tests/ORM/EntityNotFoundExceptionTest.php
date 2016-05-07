<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\EntityNotFoundException;
use PHPUnit_Framework_TestCase;

/**
 * Tests for {@see \Doctrine\ORM\EntityNotFoundException}
 *
 * @covers \Doctrine\ORM\EntityNotFoundException
 */
class EntityNotFoundExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testFromClassNameAndIdentifier()
    {
        $exception = EntityNotFoundException::fromClassNameAndIdentifier(
            'foo',
            array('foo' => 'bar')
        );

        self::assertInstanceOf('Doctrine\ORM\EntityNotFoundException', $exception);
        self::assertSame('Entity of type \'foo\' for IDs foo(bar) was not found', $exception->getMessage());

        $exception = EntityNotFoundException::fromClassNameAndIdentifier(
            'foo',
            array()
        );

        self::assertInstanceOf('Doctrine\ORM\EntityNotFoundException', $exception);
        self::assertSame('Entity of type \'foo\' was not found', $exception->getMessage());
    }
}

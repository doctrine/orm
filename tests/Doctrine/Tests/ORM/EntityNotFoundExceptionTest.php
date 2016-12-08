<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\EntityNotFoundException;

/**
 * Tests for {@see \Doctrine\ORM\EntityNotFoundException}
 *
 * @covers \Doctrine\ORM\EntityNotFoundException
 */
class EntityNotFoundExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testFromClassNameAndIdentifier()
    {
        $exception = EntityNotFoundException::fromClassNameAndIdentifier(
            'foo',
            ['foo' => 'bar']
        );

        $this->assertInstanceOf(EntityNotFoundException::class, $exception);
        $this->assertSame('Entity of type \'foo\' for IDs foo(bar) was not found', $exception->getMessage());

        $exception = EntityNotFoundException::fromClassNameAndIdentifier(
            'foo',
            []
        );

        $this->assertInstanceOf(EntityNotFoundException::class, $exception);
        $this->assertSame('Entity of type \'foo\' was not found', $exception->getMessage());
    }
}

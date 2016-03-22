<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\UnknownEntityRepositoryMethod;
use PHPUnit_Framework_TestCase;

/**
 * Tests for {@see \Doctrine\ORM\UnknownEntityRepositoryMethod}
 *
 * @covers \Doctrine\ORM\UnknownEntityRepositoryMethod
 */
class UnknownEntityRepositoryMethodTest extends PHPUnit_Framework_TestCase
{
    public function testFromClassNameAndIdentifier()
    {
        $exception = new UnknownEntityRepositoryMethod('foo', 'bar')

        $this->assertInstanceOf('Doctrine\ORM\UnknownEntityRepositoryMethod', $exception);
        $this->assertSame('Entity of type \'foo\' has no method bar() in the Repository class.Use findBy or findOneBy or implement the method.', $exception->getMessage());

    }
}

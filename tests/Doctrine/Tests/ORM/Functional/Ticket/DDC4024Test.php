<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\NonUniqueResultException;

/**
 * @group DDC4024
 */
final class DDC4024Test extends \Doctrine\Tests\DoctrineTestCase
{
    public function testConstructorShouldUseProvidedMessage() : void
    {
        $exception = new NonUniqueResultException('Testing');

        self::assertSame('Testing', $exception->getMessage());
    }

    public function testADefaultMessageShouldBeUsedWhenNothingWasProvided() : void
    {
        $exception = new NonUniqueResultException();

        self::assertSame(NonUniqueResultException::DEFAULT_MESSAGE, $exception->getMessage());
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Tests\DoctrineTestCase;

/** @group DDC4024 */
final class DDC4024Test extends DoctrineTestCase
{
    public function testConstructorShouldUseProvidedMessage(): void
    {
        $exception = new NonUniqueResultException('Testing');

        self::assertSame('Testing', $exception->getMessage());
    }

    public function testADefaultMessageShouldBeUsedWhenNothingWasProvided(): void
    {
        $exception = new NonUniqueResultException();

        self::assertSame(NonUniqueResultException::DEFAULT_MESSAGE, $exception->getMessage());
    }
}

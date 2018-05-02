<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Exception\NonUniqueResult;
use Doctrine\Tests\DoctrineTestCase;

/**
 * @group DDC4024
 */
final class DDC4024Test extends DoctrineTestCase
{
    public function testConstructorShouldUseProvidedMessage() : void
    {
        $exception = new NonUniqueResult('Testing');

        self::assertSame('Testing', $exception->getMessage());
    }

    public function testADefaultMessageShouldBeUsedWhenNothingWasProvided() : void
    {
        $exception = new NonUniqueResult();

        self::assertSame(NonUniqueResult::DEFAULT_MESSAGE, $exception->getMessage());
    }
}

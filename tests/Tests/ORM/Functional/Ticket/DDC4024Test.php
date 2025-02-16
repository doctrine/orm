<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\NonUniqueResultException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('DDC4024')]
final class DDC4024Test extends TestCase
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

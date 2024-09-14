<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Deprecations\Deprecation;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use PHPUnit\Framework\TestCase;

use const CASE_LOWER;

final class UnderscoreNamingStrategyTest extends TestCase
{
    use VerifyDeprecations;

    public function testDeprecationMessage(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/7908');

        new UnderscoreNamingStrategy(CASE_LOWER, false);
    }

    public function testNoDeprecationMessageWhenNumberAwareEnabled(): void
    {
        $before = Deprecation::getTriggeredDeprecations()['https://github.com/doctrine/orm/pull/7908'] ?? 0;

        new UnderscoreNamingStrategy(CASE_LOWER, true);

        $after = Deprecation::getTriggeredDeprecations()['https://github.com/doctrine/orm/pull/7908'] ?? 0;

        self::assertSame($before, $after);
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mapping;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use PHPUnit\Framework\TestCase;

use const CASE_LOWER;

final class UnderscoreNamingStrategyTest extends TestCase
{
    /** @test */
    public function checkDeprecationMessage(): void
    {
        $before = Deprecation::getTriggeredDeprecations()['https://github.com/doctrine/orm/pull/7908'] ?? 0;

        new UnderscoreNamingStrategy(CASE_LOWER, false);

        $after = Deprecation::getTriggeredDeprecations()['https://github.com/doctrine/orm/pull/7908'] ?? 0;

        $this->assertSame($before + 1, $after);
    }

    /** @test */
    public function checNoDeprecationMessageWhenNumberAwareEnabled(): void
    {
        $before = Deprecation::getTriggeredDeprecations()['https://github.com/doctrine/orm/pull/7908'] ?? 0;

        new UnderscoreNamingStrategy(CASE_LOWER, true);

        $after = Deprecation::getTriggeredDeprecations()['https://github.com/doctrine/orm/pull/7908'] ?? 0;

        $this->assertSame($before, $after);
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Adds methods for phpunit 8 that were introduced / renamed in later versions
 */
class PHPUnit8PolyfilledTestCase extends TestCase
{
    public static function assertDoesNotMatchRegularExpression(
        string $pattern,
        string $string,
        string $message = ''
    ): void {
        self::assertNotRegExp($pattern, $string, $message);
    }

    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        self::assertRegExp($pattern, $string, $message);
    }

    public static function assertFileDoesNotExist(string $filename, string $message = ''): void
    {
        self::assertFileNotExists($filename, $message);
    }
}

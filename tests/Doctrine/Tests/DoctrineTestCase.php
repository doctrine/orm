<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;

/**
 * Base testcase class for all Doctrine testcases.
 */
abstract class DoctrineTestCase extends TestCase
{
    public static function assertDoesNotMatchRegularExpression(
        string $pattern,
        string $string,
        string $message = ''
    ): void {
        // Forward-compatibility wrapper for phpunit 9 : can be removed once phpunit 8 / php 7.2 support is dropped.
        if (self::isPhpunit9()) {
            parent::assertDoesNotMatchRegularExpression($pattern, $string, $message);
        } else {
            self::assertNotRegExp($pattern, $string, $message);
        }
    }

    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        // Forward-compatibility wrapper for phpunit 9 : can be removed once phpunit 8 / php 7.2 support is dropped.
        if (self::isPhpunit9()) {
            parent::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            self::assertRegExp($pattern, $string, $message);
        }
    }

    public static function assertFileDoesNotExist(string $filename, string $message = ''): void
    {
        // Forward-compatibility wrapper for phpunit 9 : can be removed once phpunit 8 / php 7.2 support is dropped.
        if (self::isPhpunit9()) {
            parent::assertFileDoesNotExist($filename, $message);
        } else {
            self::assertFileNotExists($filename, $message);
        }
    }

    /**
     * Check if we're running in phpunit >= 9.0
     */
    private static function isPhpunit9(): bool
    {
        return (int) Version::series() >= 9;
    }
}

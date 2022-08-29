<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * Base testcase class for all Doctrine testcases.
 *
 * This includes polyfillable logic for PHPUnit compatibility.
 */
abstract class DoctrineTestCase extends TestCase
{
    /** @var array<string,string> */
    private static array $phpunitMethodRenames = [
        'assertMatchesRegularExpression' => 'assertRegExp', // can be removed when PHPUnit 9 is minimum
        'assertDoesNotMatchRegularExpression' => 'assertNotRegExp', // can be removed when PHPUnit 9 is minimum
        'assertFileDoesNotExist' => 'assertFileNotExists', // can be removed PHPUnit 9 is minimum
    ];

    /** @param array<mixed> $arguments */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        if (isset(self::$phpunitMethodRenames[$method])) {
            $method = self::$phpunitMethodRenames[$method];

            return self::$method(...$arguments);
        }

        throw new BadMethodCallException(sprintf('%s::%s does not exist', static::class, $method));
    }

    /** @param array<mixed> $arguments */
    public function __call(string $method, array $arguments): mixed
    {
        if ($method === 'createStub') {
            return $this->getMockBuilder(...$arguments)
                ->disableOriginalConstructor()
                ->disableOriginalClone()
                ->disableArgumentCloning()
                ->disallowMockingUnknownTypes()
                ->getMock();
        }

        if (isset(self::$phpunitMethodRenames[$method])) {
            $method = self::$phpunitMethodRenames[$method];

            return self::$method(...$arguments);
        }

        throw new BadMethodCallException(sprintf('%s::%s does not exist', static::class, $method));
    }
}

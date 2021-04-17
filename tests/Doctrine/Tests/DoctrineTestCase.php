<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Base testcase class for all Doctrine testcases.
 *
 * This includes polyfillable logic for PHPUnit compatibility.
 */
abstract class DoctrineTestCase extends TestCase
{
    public static function __callStatic(string $method, array $arguments)
    {
        if ($method === 'assertMatchesRegularExpression') {
            self::assertRegExp(...$arguments);
        } elseif ($method === 'assertFileDoesNotExist') {
            self::assertFileNotExists(...$arguments);
        }

        return null;
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        if ($method === 'createStub') {
            return $this->getMockBuilder(...$arguments)
                ->disableOriginalConstructor()
                ->disableOriginalClone()
                ->disableArgumentCloning()
                ->disallowMockingUnknownTypes()
                ->getMock();
        } elseif ($method === 'assertMatchesRegularExpression') {
            self::assertRegExp(...$arguments);
        } elseif ($method === 'assertFileDoesNotExist') {
            self::assertFileNotExists(...$arguments);
        }

        return null;
    }
}

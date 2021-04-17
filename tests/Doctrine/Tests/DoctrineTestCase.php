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
    /**
     * @param array<mixed> $arguments
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if ($method === 'assertMatchesRegularExpression') {
            return self::assertRegExp(...$arguments);
        } elseif ($method === 'assertDoesNotMatchRegularExpression') {
            return self::assertNotRegExp(...$arguments);
        } elseif ($method === 'assertFileDoesNotExist') {
            return self::assertFileNotExists(...$arguments);
        }

        throw new \BadMethodCallException(sprintf('%s::%s does not exist', get_called_class(), $method));
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
            return self::assertRegExp(...$arguments);
        } elseif ($method === 'assertDoesNotMatchRegularExpression') {
            return self::assertNotRegExp(...$arguments);
        } elseif ($method === 'assertFileDoesNotExist') {
            return self::assertFileNotExists(...$arguments);
        }

        throw new \BadMethodCallException(sprintf('%s::%s does not exist', get_called_class(), $method));
    }
}

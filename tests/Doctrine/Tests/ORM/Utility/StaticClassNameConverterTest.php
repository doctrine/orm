<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\Utility\StaticClassNameConverter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Doctrine\ORM\Utility\StaticClassNameConverter
 */
class StaticClassNameConverterTest extends TestCase
{
    /**
     * @dataProvider classNamesProvider
     */
    public function testClassNameConversion(string $givenClassName, string $expectedClassName) : void
    {
        self::assertSame($expectedClassName, StaticClassNameConverter::getRealClass($givenClassName));
    }

    public function classNamesProvider() : array
    {
        return [
            ['foo', 'foo'],
            ['foo__PM__bar', 'foo__PM__bar'],
            ['foo\\__PM__\\baz\\bar', 'baz'],
            ['foo\\__PM__\\baz\\bar\\taz', 'baz\\bar'],
            ['foo\\aaa\\__PM__\\baz\\bar\\taz', 'baz\\bar'],
        ];
    }
}

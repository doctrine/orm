<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\Utility\StaticClassNameConverter;
use PHPUnit\Framework\TestCase;
use function array_pop;
use function explode;
use function implode;

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

    /**
     * @dataProvider classNamesProvider
     * @runInSeparateProcess
     */
    public function testClassNameConversionFromObject(string $givenClassName, string $expectedClassName) : void
    {
        $namespaceParts = explode('\\', $givenClassName);
        $className      = array_pop($namespaceParts);
        $namespace      = implode('\\', $namespaceParts);

        eval('namespace ' . $namespace . ' { class ' . $className . ' {} }');

        self::assertSame($expectedClassName, StaticClassNameConverter::getClass(new $givenClassName()));
    }
}

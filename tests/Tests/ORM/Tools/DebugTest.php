<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use ArrayIterator;
use ArrayObject;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Debug;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

use function print_r;
use function strpos;
use function substr;

class DebugTest extends OrmTestCase
{
    public function testExportObject(): void
    {
        $obj      = new stdClass();
        $obj->foo = 'bar';
        $obj->bar = 1234;

        $var = Debug::export($obj, 2);
        self::assertEquals('stdClass', $var->__CLASS__);
    }

    public function testExportObjectWithReference(): void
    {
        $foo = 'bar';
        $bar = ['foo' => & $foo];
        $baz = (object) $bar;

        $var      = Debug::export($baz, 2);
        $baz->foo = 'tab';

        self::assertEquals('bar', $var->foo);
        self::assertEquals('tab', $bar['foo']);
    }

    public function testExportArray(): void
    {
        $array              = ['a' => 'b', 'b' => ['c', 'd' => ['e', 'f']]];
        $var                = Debug::export($array, 2);
        $expected           = $array;
        $expected['b']['d'] = 'Array(2)';
        self::assertEquals($expected, $var);
    }

    public function testExportDateTime(): void
    {
        $obj = new DateTime('2010-10-10 10:10:10', new DateTimeZone('UTC'));

        $var = Debug::export($obj, 2);
        self::assertEquals('DateTime', $var->__CLASS__);
        self::assertEquals('2010-10-10T10:10:10+00:00', $var->date);
    }

    public function testExportDateTimeImmutable(): void
    {
        $obj = new DateTimeImmutable('2010-10-10 10:10:10', new DateTimeZone('UTC'));

        $var = Debug::export($obj, 2);
        self::assertEquals('DateTimeImmutable', $var->__CLASS__);
        self::assertEquals('2010-10-10T10:10:10+00:00', $var->date);
    }

    public function testExportDateTimeZone(): void
    {
        $obj = new DateTimeImmutable('2010-10-10 12:34:56', new DateTimeZone('Europe/Rome'));

        $var = Debug::export($obj, 2);
        self::assertEquals('DateTimeImmutable', $var->__CLASS__);
        self::assertEquals('2010-10-10T12:34:56+02:00', $var->date);
    }

    public function testExportArrayTraversable(): void
    {
        $obj = new ArrayObject(['foobar']);

        $var = Debug::export($obj, 2);
        self::assertContains('foobar', $var->__STORAGE__);

        $it = new ArrayIterator(['foobar']);

        $var = Debug::export($it, 5);
        self::assertContains('foobar', $var->__STORAGE__);
    }

    /** @param array<string, int> $expected */
    #[DataProvider('provideAttributesCases')]
    public function testExportParentAttributes(TestAsset\ParentClass $class, array $expected): void
    {
        $actualRepresentation   = print_r($class, true);
        $expectedRepresentation = print_r($expected, true);

        $actualRepresentation   = substr($actualRepresentation, strpos($actualRepresentation, '('));
        $expectedRepresentation = substr($expectedRepresentation, strpos($expectedRepresentation, '('));

        self::assertSame($expectedRepresentation, $actualRepresentation);

        $var = Debug::export($class, 3);
        $var = (array) $var;
        unset($var['__CLASS__']);

        self::assertSame($expected, $var);
    }

    public function testCollectionsAreCastIntoArrays(): void
    {
        $collection = new ArrayCollection();
        $collection->add('foo');
        $collection->add('bar');

        $var = Debug::export($collection, 2);
        self::assertEquals(['foo', 'bar'], $var);
    }

    /** @phpstan-return array<string, array{TestAsset\ParentClass, mixed[]}> */
    public static function provideAttributesCases(): iterable
    {
        return [
            'different-attributes' => [
                new TestAsset\ChildClass(),
                [
                    'parentPublicAttribute' => 1,
                    'parentProtectedAttribute:protected' => 2,
                    'parentPrivateAttribute:Doctrine\Tests\ORM\Tools\TestAsset\ParentClass:private' => 3,
                    'childPublicAttribute' => 4,
                    'childProtectedAttribute:protected' => 5,
                    'childPrivateAttribute:Doctrine\Tests\ORM\Tools\TestAsset\ChildClass:private' => 6,
                ],
            ],
            'same-attributes' => [
                new TestAsset\ChildWithSameAttributesClass(),
                [
                    'parentPublicAttribute' => 4,
                    'parentProtectedAttribute:protected' => 5,
                    'parentPrivateAttribute:Doctrine\Tests\ORM\Tools\TestAsset\ParentClass:private' => 3,
                    'parentPrivateAttribute:Doctrine\Tests\ORM\Tools\TestAsset\ChildWithSameAttributesClass:private' => 6,
                ],
            ],
        ];
    }
}

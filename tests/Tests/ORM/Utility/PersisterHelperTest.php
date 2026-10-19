<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use DateTimeImmutable;
use Doctrine\ORM\Utility\PersisterHelper;
use Doctrine\Tests\Models\Enums\AccessLevel;
use Doctrine\Tests\Models\Enums\Suit;
use Doctrine\Tests\Models\GeoNames\Admin1;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

#[CoversClass(PersisterHelper::class)]
final class PersisterHelperTest extends OrmTestCase
{
    /** @param list<mixed> $expected */
    #[DataProvider('provideValues')]
    public function testConvertToParameterValue(mixed $value, array $expected): void
    {
        self::assertSame($expected, PersisterHelper::convertToParameterValue($value, $this->getTestEntityManager()));
    }

    /** @return iterable<string, array{mixed, list<mixed>}> */
    public static function provideValues(): iterable
    {
        yield 'integer' => [17, [17]];
        yield 'zero' => [0, [0]];
        yield 'false' => [false, [false]];
        yield 'null' => [null, [null]];
        yield 'empty string' => ['', ['']];
        yield 'integer enum' => [AccessLevel::Admin, [1]];
        yield 'string enum' => [Suit::Hearts, ['H']];
        yield 'empty array' => [[], [[]]];
        yield 'duplicates' => [['first' => 'same', 9 => 'same'], [['same', 'same']]];
        yield 'nested keyed arrays' => [
            ['zero' => 0, 'false' => false, 9 => null, 'enum' => Suit::Hearts, 'nested' => ['level' => AccessLevel::User, 'empty' => []]],
            [[0, false, null, 'H', [2, []]]],
        ];

        yield 'nested empty arrays' => [[[], [[]]], [[[], [[]]]]];
    }

    #[DataProvider('provideEntityStates')]
    public function testCompositeIdentifiersRetainComponentOrderAndArrayNesting(bool $managed): void
    {
        $em      = $this->getTestEntityManager();
        $country = new Country('IT', 'Italy');
        $admin   = new Admin1(10, 'Rome', $country);

        if ($managed) {
            $em->getUnitOfWork()->registerManaged($country, ['id' => 'IT'], []);
            $em->getUnitOfWork()->registerManaged($admin, ['id' => 10, 'country' => 'IT'], []);
        }

        self::assertSame([10, 'IT'], PersisterHelper::convertToParameterValue($admin, $em));
        self::assertSame(
            [[10, 'IT', 10, 'IT', ['IT', 'H']]],
            PersisterHelper::convertToParameterValue([
                'first' => $admin,
                9 => $admin,
                'nested' => ['country' => $country, 'enum' => Suit::Hearts],
            ], $em),
        );
    }

    /** @return iterable<string, array{bool}> */
    public static function provideEntityStates(): iterable
    {
        yield 'unmanaged' => [false];
        yield 'managed' => [true];
    }

    public function testConvertingAReferenceDoesNotInitializeIt(): void
    {
        $em        = $this->getTestEntityManager();
        $reference = $em->getReference(Country::class, 'IT');

        self::assertTrue($em->getUnitOfWork()->isUninitializedObject($reference));
        self::assertSame([['IT', 'IT']], PersisterHelper::convertToParameterValue([$reference, $reference], $em));
        self::assertTrue($em->getUnitOfWork()->isUninitializedObject($reference));
    }

    public function testUnmappedObjectsArePreserved(): void
    {
        $object = new stdClass();
        $date   = new DateTimeImmutable('2026-01-01');
        $values = [$object, $date, $object];

        self::assertSame([$values], PersisterHelper::convertToParameterValue($values, $this->getTestEntityManager()));
    }
}

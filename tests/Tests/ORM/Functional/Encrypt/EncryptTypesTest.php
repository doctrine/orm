<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Encrypt;

use BcMath\Number;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Tests\Models\Encrypt\EncryptTypesEntity;
use PHPUnit\Framework\Attributes\DataProvider;

use function class_exists;
use function get_debug_type;
use function is_resource;
use function sprintf;
use function stream_get_contents;

class EncryptTypesTest extends EncryptFunctionalTestCase
{
    /** @return iterable<string, array{string, string, mixed}> */
    public static function builtInTypeProvider(): iterable
    {
        yield Types::ASCII_STRING => ['asciiStringValue', Types::ASCII_STRING, 'ascii-only'];
        yield Types::BIGINT => ['bigintValue', Types::BIGINT, 4294967295];
        yield Types::BINARY => ['binaryValue', Types::BINARY, "\x00\x01binary-data"];
        yield Types::BLOB => ['blobValue', Types::BLOB, "\x00blob-data"];
        yield Types::BOOLEAN => ['booleanValue', Types::BOOLEAN, true];
        yield Types::DATE_MUTABLE => ['dateValue', Types::DATE_MUTABLE, new DateTime('2024-01-15')];
        yield Types::DATE_IMMUTABLE => ['dateImmutableValue', Types::DATE_IMMUTABLE, new DateTimeImmutable('2024-01-15')];
        yield Types::DATEINTERVAL => ['dateintervalValue', Types::DATEINTERVAL, new DateInterval('P2Y4DT6H8M')];
        yield Types::DATETIME_MUTABLE => ['datetimeValue', Types::DATETIME_MUTABLE, new DateTime('2024-01-15 10:30:00')];
        yield Types::DATETIME_IMMUTABLE => ['datetimeImmutableValue', Types::DATETIME_IMMUTABLE, new DateTimeImmutable('1990-06-15 08:30:00')];
        yield Types::DATETIMETZ_MUTABLE => ['datetimetzValue', Types::DATETIMETZ_MUTABLE, new DateTime('2024-01-15 10:30:00+02:00')];
        yield Types::DATETIMETZ_IMMUTABLE => ['datetimetzImmutableValue', Types::DATETIMETZ_IMMUTABLE, new DateTimeImmutable('2024-01-15 10:30:00+02:00')];
        yield Types::DECIMAL => ['decimalValue', Types::DECIMAL, '123.45'];

        if (class_exists(Number::class)) {
            yield Types::NUMBER => ['numberValue', Types::NUMBER, new Number('123.45')];
        }

        yield Types::FLOAT => ['floatValue', Types::FLOAT, 1.5];
        yield Types::ENUM => ['enumValue', Types::ENUM, 'hearts'];
        yield Types::GUID => ['guidValue', Types::GUID, 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'];
        yield Types::INTEGER => ['integerValue', Types::INTEGER, 42];
        yield Types::JSON => ['jsonValue', Types::JSON, ['a' => 1, 'b' => ['c' => true]]];
        yield Types::JSON_OBJECT => ['jsonObjectValue', Types::JSON_OBJECT, (object) ['a' => 1]];
        yield Types::JSONB => ['jsonbValue', Types::JSONB, ['a' => 1]];
        yield Types::JSONB_OBJECT => ['jsonbObjectValue', Types::JSONB_OBJECT, (object) ['b' => 2]];
        yield Types::SIMPLE_ARRAY => ['simpleArrayValue', Types::SIMPLE_ARRAY, ['a', 'b', 'c']];
        yield Types::SMALLFLOAT => ['smallfloatValue', Types::SMALLFLOAT, 1.25];
        yield Types::SMALLINT => ['smallintValue', Types::SMALLINT, 7];
        yield Types::STRING => ['stringValue', Types::STRING, 'plain-string'];
        yield Types::TEXT => ['textValue', Types::TEXT, 'long text value'];
        yield Types::TIME_MUTABLE => ['timeValue', Types::TIME_MUTABLE, new DateTime('15:30:00')];
        yield Types::TIME_IMMUTABLE => ['timeImmutableValue', Types::TIME_IMMUTABLE, new DateTimeImmutable('15:30:00')];
    }

    #[DataProvider('builtInTypeProvider')]
    public function testBuiltInTypeRoundTrips(string $property, string $typeName, mixed $value): void
    {
        $type     = Type::getType($typeName);
        $platform = $this->_em->getConnection()->getDatabasePlatform();

        $dbValue = self::normalize($type->convertToDatabaseValue($value, $platform));
        self::assertNotNull($dbValue);

        // Mirrors EncryptHelper::encryptMany(): the database value is stringified before encryption.
        $expectedDbValue  = (string) $dbValue;
        $expectedPhpValue = self::normalize($type->convertToPHPValue($expectedDbValue, $platform));

        $entity            = new EncryptTypesEntity();
        $entity->$property = $value;

        $this->_em->persist($entity);
        $this->_em->flush();

        $raw = $this->fetchRawColumn(sprintf('SELECT %s FROM encrypt_types WHERE id = ?', $property), [$entity->id]);

        self::assertNotSame($expectedDbValue, $raw);
        self::assertSame($expectedDbValue, $this->decrypt($raw));

        $this->_em->clear();

        $found = $this->_em->find(EncryptTypesEntity::class, $entity->id);

        self::assertNotNull($found);

        $hydrated = self::normalize($found->$property);

        self::assertEquals($expectedPhpValue, $hydrated);
        self::assertSame(get_debug_type($expectedPhpValue), get_debug_type($hydrated));
    }

    private static function normalize(mixed $value): mixed
    {
        return is_resource($value) ? stream_get_contents($value) : $value;
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\AsciiStringType;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateImmutableType;
use Doctrine\DBAL\Types\DateIntervalType;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateTimeTzImmutableType;
use Doctrine\DBAL\Types\DateTimeTzType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\SimpleArrayType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\TimeImmutableType;
use Doctrine\DBAL\Types\TimeType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;

use function array_merge;
use function assert;
use function enum_exists;

use const PHP_VERSION_ID;

/** @psalm-type ScalarName = 'array'|'bool'|'float'|'int'|'string' */
final class DefaultTypedFieldMapper implements TypedFieldMapper
{
    /** @var array<class-string|ScalarName, class-string<Type>|string> $typedFieldMappings */
    private $typedFieldMappings;

    private const DEFAULT_TYPED_FIELD_MAPPINGS = [
        DateInterval::class => Types::DATEINTERVAL,
        DateTime::class => Types::DATETIME_MUTABLE,
        DateTimeImmutable::class => Types::DATETIME_IMMUTABLE,
        'array' => Types::JSON,
        'bool' => Types::BOOLEAN,
        'float' => Types::FLOAT,
        'int' => Types::INTEGER,
        'string' => Types::STRING,
    ];

    /** It maps built-in Doctrine types to PHP types */
    private const BUILTIN_TYPES_MAP = [
        AsciiStringType::class => 'string',
        BigIntType::class => 'string',
        BooleanType::class => 'bool',
        DateType::class => DateTime::class,
        DateImmutableType::class => DateTimeImmutable::class,
        DateIntervalType::class => DateInterval::class,
        DateTimeType::class => DateTime::class,
        DateTimeImmutableType::class => DateTimeImmutable::class,
        DateTimeTzType::class => DateTime::class,
        DateTimeTzImmutableType::class => DateTimeImmutable::class,
        DecimalType::class => 'string',
        FloatType::class => 'float',
        GuidType::class => 'string',
        IntegerType::class => 'int',
        JsonType::class => 'array',
        SimpleArrayType::class => 'array',
        SmallIntType::class => 'int',
        StringType::class => 'string',
        TextType::class => 'string',
        TimeType::class => DateTime::class,
        TimeImmutableType::class => DateTimeImmutable::class,
    ];

    /** @param array<class-string|ScalarName, class-string<Type>|string> $typedFieldMappings */
    public function __construct(array $typedFieldMappings = [])
    {
        $this->typedFieldMappings = array_merge(self::DEFAULT_TYPED_FIELD_MAPPINGS, $typedFieldMappings);
    }

    /**
     * @internal
     *
     * @param class-string<Type> $typeName
     */
    public function getBuiltInType(string $typeName): string
    {
        return self::BUILTIN_TYPES_MAP[$typeName] ?? $typeName;
    }

    /**
     * {@inheritDoc}
     */
    public function validateAndComplete(array $mapping, ReflectionProperty $field): array
    {
        $type = $field->getType();

        if (
            ! isset($mapping['type'])
            && ($type instanceof ReflectionNamedType)
        ) {
            if (PHP_VERSION_ID >= 80100 && ! $type->isBuiltin() && enum_exists($type->getName())) {
                $mapping['enumType'] = $type->getName();

                $reflection = new ReflectionEnum($type->getName());
                $type       = $reflection->getBackingType();

                assert($type instanceof ReflectionNamedType);
            }

            if (isset($this->typedFieldMappings[$type->getName()])) {
                $mapping['type'] = $this->typedFieldMappings[$type->getName()];
            }
        }

        return $mapping;
    }
}

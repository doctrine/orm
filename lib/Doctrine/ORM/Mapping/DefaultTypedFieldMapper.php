<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use DateInterval;
use DateTime;
use DateTimeImmutable;
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

    /** @param array<class-string|ScalarName, class-string<Type>|string> $typedFieldMappings */
    public function __construct(array $typedFieldMappings = [])
    {
        $this->typedFieldMappings = array_merge(self::DEFAULT_TYPED_FIELD_MAPPINGS, $typedFieldMappings);
    }

    /**
     * {@inheritdoc}
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

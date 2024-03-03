<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use BackedEnum;
use ReflectionProperty;
use ValueError;

use function array_map;
use function is_array;

/** @deprecated use Doctrine\Persistence\Reflection\EnumReflectionProperty instead */
final class ReflectionEnumProperty extends ReflectionProperty
{
    /** @param class-string<BackedEnum> $enumType */
    public function __construct(
        private readonly ReflectionProperty $originalReflectionProperty,
        private readonly string $enumType,
    ) {
        parent::__construct(
            $originalReflectionProperty->class,
            $originalReflectionProperty->name,
        );
    }

    public function getValue(object|null $object = null): int|string|array|null
    {
        if ($object === null) {
            return null;
        }

        $enum = $this->originalReflectionProperty->getValue($object);

        if ($enum === null) {
            return null;
        }

        if (is_array($enum)) {
            return array_map(
                static fn (BackedEnum $item): int|string => $item->value,
                $enum,
            );
        }

        return $enum->value;
    }

    /**
     * @param object                                                 $object
     * @param int|string|int[]|string[]|BackedEnum|BackedEnum[]|null $value
     */
    public function setValue(mixed $object, mixed $value = null): void
    {
        if ($value !== null) {
            if (is_array($value)) {
                $value = array_map(fn (int|string|BackedEnum $item): BackedEnum => $this->initializeEnumValue($object, $item), $value);
            } else {
                $value = $this->initializeEnumValue($object, $value);
            }
        }

        $this->originalReflectionProperty->setValue($object, $value);
    }

    private function initializeEnumValue(object $object, int|string|BackedEnum $value): BackedEnum
    {
        if ($value instanceof BackedEnum) {
            return $value;
        }

        $enumType = $this->enumType;

        try {
            return $enumType::from($value);
        } catch (ValueError $e) {
            throw MappingException::invalidEnumValue(
                $object::class,
                $this->originalReflectionProperty->name,
                (string) $value,
                $enumType,
                $e,
            );
        }
    }
}

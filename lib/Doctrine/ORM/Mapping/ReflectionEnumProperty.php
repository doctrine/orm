<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use BackedEnum;
use ReflectionProperty;
use ValueError;

use function array_map;
use function get_class;
use function is_array;

final class ReflectionEnumProperty extends ReflectionProperty
{
    /**
     * @param class-string<BackedEnum> $enumType
     */
    public function __construct(
        private readonly ReflectionProperty $originalReflectionProperty,
        private readonly string $enumType
    ) {
        parent::__construct(
            $originalReflectionProperty->getDeclaringClass()->getName(),
            $originalReflectionProperty->getName()
        );
    }

    public function getValue(?object $object = null): int|string|array|null
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
                $enum
            );
        }

        return $enum->value;
    }

    /**
     * @param object                         $object
     * @param int|string|int[]|string[]|null $value
     */
    public function setValue(mixed $object, mixed $value = null): void
    {
        if ($value !== null) {
            if (is_array($value)) {
                $value = array_map(function ($item) use ($object): BackedEnum {
                    return $this->initializeEnumValue($object, $item);
                }, $value);
            } else {
                $value = $this->initializeEnumValue($object, $value);
            }
        }

        $this->originalReflectionProperty->setValue($object, $value);
    }

    private function initializeEnumValue(object $object, int|string $value): BackedEnum
    {
        $enumType = $this->enumType;

        try {
            return $enumType::from($value);
        } catch (ValueError $e) {
            throw MappingException::invalidEnumValue(
                get_class($object),
                $this->originalReflectionProperty->getName(),
                (string) $value,
                $enumType,
                $e
            );
        }
    }
}

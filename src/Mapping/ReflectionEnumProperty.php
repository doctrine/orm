<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use BackedEnum;
use ReflectionProperty;
use ReturnTypeWillChange;
use ValueError;

use function array_map;
use function get_class;
use function is_array;

class ReflectionEnumProperty extends ReflectionProperty
{
    /** @var ReflectionProperty */
    private $originalReflectionProperty;

    /** @var class-string<BackedEnum> */
    private $enumType;

    /** @param class-string<BackedEnum> $enumType */
    public function __construct(ReflectionProperty $originalReflectionProperty, string $enumType)
    {
        $this->originalReflectionProperty = $originalReflectionProperty;
        $this->enumType                   = $enumType;

        parent::__construct(
            $originalReflectionProperty->class,
            $originalReflectionProperty->name
        );
    }

    /**
     * {@inheritDoc}
     *
     * @param object|null $object
     *
     * @return int|string|int[]|string[]|null
     */
    #[ReturnTypeWillChange]
    public function getValue($object = null)
    {
        if ($object === null) {
            return null;
        }

        $enum = $this->originalReflectionProperty->getValue($object);

        if ($enum === null) {
            return null;
        }

        if (is_array($enum)) {
            return array_map(static function (BackedEnum $item): mixed {
                return $item->value;
            }, $enum);
        }

        return $enum->value;
    }

    /**
     * @param object                                                 $object
     * @param int|string|int[]|string[]|BackedEnum|BackedEnum[]|null $value
     */
    public function setValue($object, $value = null): void
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

    /**
     * @param object                $object
     * @param int|string|BackedEnum $value
     */
    private function initializeEnumValue($object, $value): BackedEnum
    {
        if ($value instanceof BackedEnum) {
            return $value;
        }

        $enumType = $this->enumType;

        try {
            return $enumType::from($value);
        } catch (ValueError $e) {
            throw MappingException::invalidEnumValue(
                get_class($object),
                $this->originalReflectionProperty->name,
                (string) $value,
                $enumType,
                $e
            );
        }
    }
}

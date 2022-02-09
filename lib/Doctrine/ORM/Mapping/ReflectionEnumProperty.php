<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use BackedEnum;
use ReflectionProperty;
use ReturnTypeWillChange;
use ValueError;

use function assert;
use function get_class;
use function is_int;
use function is_string;
use function is_array;
use function array_map;

class ReflectionEnumProperty extends ReflectionProperty
{
    /** @var ReflectionProperty */
    private $originalReflectionProperty;

    /** @var class-string<BackedEnum> */
    private $enumType;

    /**
     * @param class-string<BackedEnum> $enumType
     */
    public function __construct(ReflectionProperty $originalReflectionProperty, string $enumType)
    {
        $this->originalReflectionProperty = $originalReflectionProperty;
        $this->enumType                   = $enumType;

        parent::__construct(
            $originalReflectionProperty->getDeclaringClass()->getName(),
            $originalReflectionProperty->getName()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @param object|null $object
     *
     * @return int|string|null
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
            return array_map(function ($item) {
                return $item->value;
            }, $enum);
        }

        return $enum->value;
    }

    /**
     * @param object $object
     * @param mixed  $value
     */
    public function setValue($object, $value = null): void
    {
        if ($value !== null) {
            $enumType = $this->enumType;

            if (is_array($value)) {
                $value = array_map(function ($item) use ($enumType, $object) {
                    try {
                        return $enumType::from($item);
                    } catch (ValueError $e) {
                        assert(is_string($item) || is_int($item));

                        throw MappingException::invalidEnumValue(
                            get_class($object),
                            $this->originalReflectionProperty->getName(),
                            (string) $item,
                            $enumType,
                            $e
                        );
                    }
                }, $value);
            } else {
                try {
                    $value = $enumType::from($value);
                } catch (ValueError $e) {
                    assert(is_string($value) || is_int($value));

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

        $this->originalReflectionProperty->setValue($object, $value);
    }
}

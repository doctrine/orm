<?php

namespace Doctrine\ORM\Mapping\PropertyAccessors;

use BackedEnum;

class EnumPropertyAccessor implements PropertyAccessor
{
    public function __construct(private PropertyAccessor $parent, private string $enumType)
    {
    }

    public function setValue(object $object, mixed $value): void
    {
        if ($value !== null) {
            $value = $this->toEnum($value);
        }

        $this->parent->setValue($object, $value);
    }

    public function getValue(object $object): mixed
    {
        $enum = $this->parent->getValue($object);

        if ($enum === null) {
            return null;
        }

        return $this->fromEnum($enum);
    }

    private function fromEnum($enum)
    {
        if (is_array($enum)) {
            return array_map(static function (BackedEnum $enum) {
                return $enum->value;
            }, $enum);
        }

        return $enum->value;
    }

    /**
     * @param int|string|int[]|string[]|BackedEnum|BackedEnum[] $value
     *
     * @return ($value is int|string|BackedEnum ? BackedEnum : BackedEnum[])
     */
    private function toEnum($value)
    {
        if ($value instanceof BackedEnum) {
            return $value;
        }

        if (is_array($value)) {
            $v = reset($value);
            if ($v instanceof BackedEnum) {
                return $value;
            }

            return array_map([$this->enumType, 'from'], $value);
        }

        return $this->enumType::from($value);
    }
}

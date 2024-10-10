<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\PropertyAccessors;

interface PropertyAccessor
{
    public function setValue(object $object, mixed $value): void;

    public function getValue(object $object): mixed;
}

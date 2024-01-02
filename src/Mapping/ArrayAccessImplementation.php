<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use InvalidArgumentException;

use function property_exists;

/** @internal */
trait ArrayAccessImplementation
{
    /** @param string $offset */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    /** @param string $offset */
    public function offsetGet(mixed $offset): mixed
    {
        if (! property_exists($this, $offset)) {
            throw new InvalidArgumentException('Undefined property: ' . $offset);
        }

        return $this->$offset;
    }

    /** @param string $offset */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    /** @param string $offset */
    public function offsetUnset(mixed $offset): void
    {
        $this->$offset = null;
    }
}

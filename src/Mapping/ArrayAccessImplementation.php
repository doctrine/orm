<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Deprecations\Deprecation;
use InvalidArgumentException;

use function property_exists;

/** @internal */
trait ArrayAccessImplementation
{
    /** @param string $offset */
    public function offsetExists(mixed $offset): bool
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/11211',
            'Using ArrayAccess on %s is deprecated and will not be possible in Doctrine ORM 4.0. Use the corresponding property instead.',
            static::class,
        );

        return isset($this->$offset);
    }

    /** @param string $offset */
    public function offsetGet(mixed $offset): mixed
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/11211',
            'Using ArrayAccess on %s is deprecated and will not be possible in Doctrine ORM 4.0. Use the corresponding property instead.',
            static::class,
        );

        if (! property_exists($this, $offset)) {
            throw new InvalidArgumentException('Undefined property: ' . $offset);
        }

        return $this->$offset;
    }

    /** @param string $offset */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/11211',
            'Using ArrayAccess on %s is deprecated and will not be possible in Doctrine ORM 4.0. Use the corresponding property instead.',
            static::class,
        );

        $this->$offset = $value;
    }

    /** @param string $offset */
    public function offsetUnset(mixed $offset): void
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/11211',
            'Using ArrayAccess on %s is deprecated and will not be possible in Doctrine ORM 4.0. Use the corresponding property instead.',
            static::class,
        );

        $this->$offset = null;
    }
}

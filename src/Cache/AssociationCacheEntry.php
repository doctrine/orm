<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

class AssociationCacheEntry implements CacheEntry
{
    /**
     * @param array<string, mixed> $identifier The entity identifier.
     * @param class-string         $class      The entity class name
     */
    public function __construct(
        public readonly string $class,
        public readonly array $identifier,
    ) {
    }

    /**
     * Creates a new AssociationCacheEntry
     *
     * This method allow Doctrine\Common\Cache\PhpFileCache compatibility
     *
     * @param array<string, mixed> $values array containing property values
     */
    public static function __set_state(array $values): self
    {
        return new self($values['class'], $values['identifier']);
    }
}

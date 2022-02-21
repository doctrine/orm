<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Association cache entry
 */
class AssociationCacheEntry implements CacheEntry
{
    /**
     * The entity identifier
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var array<string, mixed>
     */
    public array $identifier;

    /**
     * The entity class name
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @psalm-var class-string
     */
    public string $class;

    /**
     * @param array<string, mixed> $identifier The entity identifier.
     * @psalm-param class-string $class
     */
    public function __construct(string $class, array $identifier)
    {
        $this->class      = $class;
        $this->identifier = $identifier;
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

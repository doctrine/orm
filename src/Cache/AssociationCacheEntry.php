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
    public $identifier;

    /**
     * The entity class name
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var class-string
     */
    public $class;

    /**
     * @param class-string         $class      The entity class.
     * @param array<string, mixed> $identifier The entity identifier.
     */
    public function __construct($class, array $identifier)
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
     *
     * @return AssociationCacheEntry
     */
    public static function __set_state(array $values)
    {
        return new self($values['class'], $values['identifier']);
    }
}

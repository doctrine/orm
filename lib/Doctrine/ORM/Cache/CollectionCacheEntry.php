<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

class CollectionCacheEntry implements CacheEntry
{
    /** @param CacheKey[] $identifiers List of entity identifiers hold by the collection */
    public function __construct(public readonly array $identifiers)
    {
    }

    /**
     * Creates a new CollectionCacheEntry
     *
     * This method allows for Doctrine\Common\Cache\PhpFileCache compatibility
     *
     * @param array<string, mixed> $values array containing property values
     */
    public static function __set_state(array $values): CollectionCacheEntry
    {
        return new self($values['identifiers']);
    }
}

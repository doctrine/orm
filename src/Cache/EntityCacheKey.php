<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use function array_map;
use function hash;
use function ksort;
use function serialize;
use function strtolower;

/**
 * Defines entity classes roles to be stored in the cache region.
 */
class EntityCacheKey extends CacheKey
{
    /**
     * The entity identifier
     *
     * @var array<string, mixed>
     */
    public readonly array $identifier;

    /**
     * @param class-string         $entityClass The entity class name. In a inheritance hierarchy it should always be the root entity class.
     * @param array<string, mixed> $identifier  The entity identifier
     */
    public function __construct(
        public readonly string $entityClass,
        array $identifier,
    ) {
        ksort($identifier);

        $this->identifier = $identifier;

        parent::__construct(hash('sha256', serialize([strtolower($entityClass), array_map('strval', $identifier)])));
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use function implode;
use function ksort;
use function str_replace;
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

        parent::__construct(str_replace('\\', '.', strtolower($entityClass) . '_' . implode(' ', $identifier)));
    }
}

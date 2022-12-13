<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use function implode;
use function ksort;
use function str_replace;
use function strtolower;

/**
 * Defines entity collection roles to be stored in the cache region.
 */
class CollectionCacheKey extends CacheKey
{
    /**
     * The owner entity identifier
     *
     * @var array<string, mixed>
     */
    public readonly array $ownerIdentifier;

    /**
     * @param array<string, mixed> $ownerIdentifier The identifier of the owning entity.
     * @param class-string         $entityClass     The owner entity class
     */
    public function __construct(
        public readonly string $entityClass,
        public readonly string $association,
        array $ownerIdentifier,
    ) {
        ksort($ownerIdentifier);

        $this->ownerIdentifier = $ownerIdentifier;

        parent::__construct(str_replace('\\', '.', strtolower($entityClass)) . '_' . implode(' ', $ownerIdentifier) . '__' . $association);
    }
}

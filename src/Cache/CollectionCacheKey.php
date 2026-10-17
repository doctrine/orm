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
     * @param class-string         $entityClass     The owner entity class.
     * @param array<string, mixed> $ownerIdentifier The identifier of the owning entity.
     * @param string               $filterHash      The SQL filter context for this variant.
     * @param string               $version         The shared collection version, or empty for its version entry.
     */
    public function __construct(
        public readonly string $entityClass,
        public readonly string $association,
        array $ownerIdentifier,
        public readonly string $filterHash = '',
        string $version = '',
    ) {
        ksort($ownerIdentifier);

        $this->ownerIdentifier = $ownerIdentifier;

        $filterHash = $filterHash === '' ? '' : '_' . $filterHash;

        $hash = str_replace('\\', '.', strtolower($entityClass)) . '_' . implode(' ', $ownerIdentifier) . '__' . $association . $filterHash;

        parent::__construct($version === '' ? $hash : 'collection_' . $version . '_' . $hash);
    }
}

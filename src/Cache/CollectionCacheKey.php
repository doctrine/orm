<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use function array_map;
use function hash;
use function ksort;
use function serialize;
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
     */
    public function __construct(
        public readonly string $entityClass,
        public readonly string $association,
        array $ownerIdentifier,
        string $filterHash = '',
    ) {
        ksort($ownerIdentifier);

        $this->ownerIdentifier = $ownerIdentifier;

        parent::__construct(hash('sha256', serialize([
            strtolower($entityClass),
            array_map('strval', $ownerIdentifier),
            $association,
            $filterHash,
        ])));
    }
}

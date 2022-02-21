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
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var array<string, mixed>
     */
    public array $ownerIdentifier;

    /**
     * The owner entity class
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @psalm-var class-string
     */
    public string $entityClass;

    /**
     * The association name
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     */
    public string $association;

    /**
     * @param array<string, mixed> $ownerIdentifier The identifier of the owning entity.
     * @psalm-param class-string $entityClass
     */
    public function __construct(string $entityClass, string $association, array $ownerIdentifier)
    {
        ksort($ownerIdentifier);

        $this->ownerIdentifier = $ownerIdentifier;
        $this->entityClass     = $entityClass;
        $this->association     = $association;
        $this->hash            = str_replace('\\', '.', strtolower($entityClass)) . '_' . implode(' ', $ownerIdentifier) . '__' . $association;
    }
}

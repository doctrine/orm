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
    public $ownerIdentifier;

    /**
     * The owner entity class
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var string
     * @psalm-var class-string
     */
    public $entityClass;

    /**
     * The association name
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var string
     */
    public $association;

    /**
     * @param string               $entityClass     The entity class.
     * @param string               $association     The field name that represents the association.
     * @param array<string, mixed> $ownerIdentifier The identifier of the owning entity.
     * @psalm-param class-string $entityClass
     */
    public function __construct($entityClass, $association, array $ownerIdentifier)
    {
        ksort($ownerIdentifier);

        $this->ownerIdentifier = $ownerIdentifier;
        $this->entityClass     = (string) $entityClass;
        $this->association     = (string) $association;
        $this->hash            = str_replace('\\', '.', strtolower($entityClass)) . '_' . implode(' ', $ownerIdentifier) . '__' . $association;
    }
}

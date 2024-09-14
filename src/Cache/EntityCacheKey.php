<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use function array_map;
use function implode;
use function is_scalar;
use function ksort;
use function md5;
use function serialize;
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
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var array<string, mixed>
     */
    public $identifier;

    /**
     * The entity class name
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var string
     * @psalm-var class-string
     */
    public $entityClass;

    /**
     * @param string               $entityClass The entity class name. In a inheritance hierarchy it should always be the root entity class.
     * @param array<string, mixed> $identifier  The entity identifier
     * @psalm-param class-string $entityClass
     */
    public function __construct($entityClass, array $identifier)
    {
        ksort($identifier);

        $this->identifier  = $identifier;
        $this->entityClass = $entityClass;

        parent::__construct(
            str_replace(
                '\\',
                '.',
                strtolower($entityClass) . '_' . $this->serializeIdentifier($identifier)
            )
        );
    }

    /** @param array<int|string|object> $identifier */
    private function serializeIdentifier(array $identifier): string
    {
        return implode(' ', array_map(
            static function ($id) {
                return is_scalar($id) ? $id : md5(serialize($id));
            },
            $identifier
        ));
    }
}

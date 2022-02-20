<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\EntityManagerInterface;

use function array_map;

/**
 * Entity cache entry
 */
class EntityCacheEntry implements CacheEntry
{
    /**
     * The entity map data
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var array<string,mixed>
     */
    public array $data;

    /**
     * The entity class name
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @psalm-var class-string
     */
    public string $class;

    /**
     * @param array<string,mixed> $data The entity data.
     * @psalm-param class-string $class
     */
    public function __construct(string $class, array $data)
    {
        $this->class = $class;
        $this->data  = $data;
    }

    /**
     * Creates a new EntityCacheEntry
     *
     * This method allow Doctrine\Common\Cache\PhpFileCache compatibility
     *
     * @param array<string,mixed> $values array containing property values
     */
    public static function __set_state(array $values): self
    {
        return new self($values['class'], $values['data']);
    }

    /**
     * Retrieves the entity data resolving cache entries
     *
     * @return array<string, mixed>
     */
    public function resolveAssociationEntries(EntityManagerInterface $em): array
    {
        return array_map(static function ($value) use ($em) {
            if (! ($value instanceof AssociationCacheEntry)) {
                return $value;
            }

            return $em->getReference($value->class, $value->identifier);
        }, $this->data);
    }
}

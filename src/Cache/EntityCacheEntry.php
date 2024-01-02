<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\EntityManagerInterface;

use function array_map;

class EntityCacheEntry implements CacheEntry
{
    /**
     * @param array<string,mixed> $data The entity map data
     * @psalm-param class-string $class The entity class name
     */
    public function __construct(
        public readonly string $class,
        public readonly array $data,
    ) {
    }

    /**
     * Creates a new EntityCacheEntry
     *
     * This method allows Doctrine\Common\Cache\PhpFileCache compatibility
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

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class OneToManyAssociationMapping extends ToManyAssociationMapping
{
    /** @param mixed[] $mappingArray */
    public static function fromMappingArrayAndName(array $mappingArray, string $name): self
    {
        $mapping = parent::fromMappingArray($mappingArray);

        // OneToMany-side MUST be inverse (must have mappedBy)
        if (! isset($mapping['mappedBy'])) {
            throw MappingException::oneToManyRequiresMappedBy($name, $mapping['fieldName']);
        }

        $mapping['orphanRemoval']   = isset($mapping['orphanRemoval']) && $mapping['orphanRemoval'];
        $mapping['isCascadeRemove'] = $mapping['orphanRemoval'] || $mapping['isCascadeRemove'];

        $mapping->assertMappingOrderBy();

        return $mapping;
    }
}

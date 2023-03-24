<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class ManyToManyAssociationMapping extends ToManyAssociationMapping
{
    public array|null $relationToSourceKeyColumns = null;
    public array|null $relationToTargetKeyColumns = null;

    /** @param mixed[] $mappingArray */
    public static function fromMappingArray(array $mappingArray): static
    {
        $mapping = parent::fromMappingArray($mappingArray);

        $mapping->assertMappingOrderBy();

        return $mapping;
    }
}

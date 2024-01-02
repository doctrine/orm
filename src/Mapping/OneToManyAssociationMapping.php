<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class OneToManyAssociationMapping extends ToManyInverseSideMapping
{
    /**
     * @param mixed[] $mappingArray
     * @psalm-param array{
     *     fieldName: string,
     *     sourceEntity: class-string,
     *     targetEntity: class-string,
     *     joinTable?: mixed[]|null,
     *     type?: int,
     *     isOwningSide: bool, ...} $mappingArray
     */
    public static function fromMappingArray(array $mappingArray): static
    {
        $mapping = parent::fromMappingArray($mappingArray);

        if ($mapping->orphanRemoval && ! $mapping->isCascadeRemove()) {
            $mapping->cascade[] = 'remove';
        }

        return $mapping;
    }

    /**
     * @param mixed[] $mappingArray
     * @psalm-param array{
     *     fieldName: string,
     *     sourceEntity: class-string,
     *     targetEntity: class-string,
     *     joinTable?: mixed[]|null,
     *     type?: int,
     *     isOwningSide: bool, ...} $mappingArray
     */
    public static function fromMappingArrayAndName(array $mappingArray, string $name): static
    {
        $mapping = self::fromMappingArray($mappingArray);

        // OneToMany-side MUST be inverse (must have mappedBy)
        if (! isset($mapping->mappedBy)) {
            throw MappingException::oneToManyRequiresMappedBy($name, $mapping->fieldName);
        }

        return $mapping;
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

abstract class ToOneInverseSideMapping extends InverseSideMapping
{
    /**
     * @param mixed[]      $mappingArray
     * @param class-string $name
     * @psalm-param array{
     *     fieldName: string,
     *     sourceEntity: class-string,
     *     targetEntity: class-string,
     *     isOwningSide: bool,
     *     } $mappingArray
     */
    public static function fromMappingArrayAndName(
        array $mappingArray,
        string $name,
    ): static {
        $mapping = static::fromMappingArray($mappingArray);

        if (isset($mapping->id) && $mapping->id === true) {
            throw MappingException::illegalInverseIdentifierAssociation($name, $mapping->fieldName);
        }

        if ($mapping->orphanRemoval) {
            if (! $mapping->isCascadeRemove()) {
                $mapping->cascade[] = 'remove';
            }

            $mapping->unique = null;
        }

        return $mapping;
    }
}

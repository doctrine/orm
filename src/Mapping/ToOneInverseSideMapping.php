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
     *     cascade?: list<'persist'|'remove'|'detach'|'refresh'|'all'>,
     *     fetch?: ClassMetadata::FETCH_*|null,
     *     inherited?: class-string|null,
     *     declared?: class-string|null,
     *     cache?: array<mixed>|null,
     *     id?: bool|null,
     *     isOnDeleteCascade?: bool|null,
     *     originalClass?: class-string|null,
     *     originalField?: string|null,
     *     orphanRemoval?: bool,
     *     unique?: bool|null,
     *     joinTable?: mixed[]|null,
     *     type?: int,
     *     isOwningSide: bool,
     * } $mappingArray
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

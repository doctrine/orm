<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayAccess;

use function in_array;
use function property_exists;

/** @template-implements ArrayAccess<string, mixed> */
final class AssociationMapping implements ArrayAccess
{
    /**
     * required for bidirectional associations
     * The name of the field that completes the bidirectional association on
     * the owning side. This key must be specified on the inverse side of a
     * bidirectional association.
     */
    public string|null $mappedBy = null;

    /**
     * required for bidirectional associations
     * The name of the field that completes the bidirectional association on
     * the inverse side. This key must be specified on the owning side of a
     * bidirectional association.
     */
    public string|null $inversedBy = null;

    /**
     * The names of persistence operations to cascade on the association.
     *
     * @var list<'persist'|'remove'|'detach'|'merge'|'refresh'|'all'>
     */
    public array|null $cascade = null;

    /**
     * one-to-many/many-to-many only
     * A map of field names (of the target entity) to sorting directions
     *
     * @var array<string, 'asc'|'desc'>
     */
    public array|null $orderBy = null;

    /**
     * The fetching strategy to use for the association, usually defaults to FETCH_LAZY.
     *
     * @var ClassMetadata::FETCH_EAGER|ClassMetadata::FETCH_LAZY
     */
    public int|null $fetch = null;

    /**
     * many-to-many only
     * Specification of the join table and its join columns (foreign keys).
     * Only valid for many-to-many mappings. Note that one-to-many associations
     * can be mapped through a join table by simply mapping the association as
     * many-to-many with a unique constraint on the join table.
     */
    public JoinTableMapping|null $joinTable = null;

    /**
     * to-many only
     * Specification of a field on target-entity that is used to index the
     * collection by. This field HAS to be either the primary key or a unique
     * column. Otherwise the collection does not contain all the entities that
     * are actually related.
     */
    public string|null $indexBy = null;

    /**
     * This is set when the association is inherited by this class from another
     * (inheritance) parent <em>entity</em> class. The value is the FQCN of the
     * topmost entity class that contains this association. (If there are
     * transient classes in the class hierarchy, these are ignored, so the
     * class property may in fact come from a class further up in the PHP class
     * hierarchy.) To-many associations initially declared in mapped
     * superclasses are <em>not</em> considered 'inherited' in the nearest
     * entity subclasses.
     *
     * @var class-string
     */
    public string|null $inherited = null;

    /**
     * This is set when the association does not appear in the current class
     * for the first time, but is initially declared in another parent
     * <em>entity or mapped superclass</em>. The value is the FQCN of the
     * topmost non-transient class that contains association information for
     * this relationship.
     */
    public string|null $declared = null;

    public array|null $cache = null;

    public bool|null $id = null;

    public bool $isCascadeRemove  = false;
    public bool $isCascadePersist = false;
    public bool $isCascadeRefresh = false;
    public bool $isCascadeMerge   = false;
    public bool $isCascadeDetach  = false;

    public bool|null $isOnDeleteCascade = null;

    public bool $isOwningSide = true;

    /** @var list<JoinColumnData> */
    public array|null $joinColumns = null;

    /** @var array<string, string> */
    public array|null $joinColumnFieldNames = null;

    /** @var list<mixed> */
    public array|null $joinTableColumns = null;

    /** @var class-string */
    public string|null $originalClass = null;

    /** @var string */
    public string|null $originalField = null;

    public bool|null $orphanRemoval = null;

    public array|null $relationToSourceKeyColumns = null;
    public array|null $relationToTargetKeyColumns = null;

    /** @var array<string, string> */
    public array|null $sourceToTargetKeyColumns = null;

    /** @var array<string, string> */
    public array|null $targetToSourceKeyColumns = null;

    public bool|null $unique = null;

    /**
     * @param string       $fieldName    The name of the field in the entity
     *                                   the association is mapped to.
     * @param class-string $sourceEntity The class name of the source entity.
     *                                   In the case of to-many associations
     *                                   initially present in mapped
     *                                   superclasses, the nearest
     *                                   <em>entity</em> subclasses will be
     *                                   considered the respective source
     *                                   entities.
     * @param class-string $targetEntity The class name of the target entity.
     *                                   If it is fully-qualified it is used as
     *                                   is. If it is a simple, unqualified
     *                                   class name the namespace is assumed to
     *                                   be the same as the namespace of the
     *                                   source entity.
     */
    public function __construct(
        public int $type,
        public string $fieldName,
        public string $sourceEntity,
        public string $targetEntity,
    ) {
    }

    /**
     * @psalm-param array{
     *     fieldName: string,
     *     sourceEntity: class-string,
     *     targetEntity: class-string,
     *     type: int,
     *     joinColumns?: mixed[]|null,
     *     joinTable?: mixed[]|null, ...} $mappingArray
     */
    public static function fromMappingArray(array $mappingArray): self
    {
        $mapping = new self(
            $mappingArray['type'],
            $mappingArray['fieldName'],
            $mappingArray['sourceEntity'],
            $mappingArray['targetEntity'],
        );
        foreach ($mappingArray as $key => $value) {
            if (in_array($key, ['type', 'fieldName', 'sourceEntity', 'targetEntity'])) {
                continue;
            }

            if ($key === 'joinColumns') {
                if ($value === null) {
                    continue;
                }

                $joinColumns = [];
                foreach ($value as $column) {
                    $joinColumns[] = JoinColumnData::fromMappingArray($column);
                }

                $mapping->joinColumns = $joinColumns;

                continue;
            }

            if ($key === 'joinTable') {
                if ($value === [] || $value === null) {
                    continue;
                }

                $mapping->joinTable = JoinTableMapping::fromMappingArray($value);

                continue;
            }

            if (property_exists($mapping, $key)) {
                $mapping->$key = $value;
            }
        }

        return $mapping;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->$offset;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === 'joinColumns') {
            $joinColumns = [];
            foreach ($value as $column) {
                $joinColumns[] = JoinColumnData::fromMappingArray($column);
            }

            $value = $joinColumns;
        }

        if ($offset === 'joinTable') {
            $value = JoinTableMapping::fromMappingArray($value);
        }

        $this->$offset = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        $this->$offset = null;
    }

    /** @return mixed[] */
    public function toArray(): array
    {
        $array = (array) $this;

        if ($array['joinColumns'] !== null) {
            $joinColumns = [];
            foreach ($array['joinColumns'] as $column) {
                $joinColumns[] = (array) $column;
            }

            $array['joinColumns'] = $joinColumns;
        }

        if ($array['joinTable'] !== null) {
            $array['joinTable'] = $array['joinTable']->toArray();
        }

        return $array;
    }
}

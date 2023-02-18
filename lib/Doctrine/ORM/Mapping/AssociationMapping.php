<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayAccess;
use Exception;

use function assert;
use function in_array;
use function property_exists;

/** @template-implements ArrayAccess<string, mixed> */
abstract class AssociationMapping implements ArrayAccess
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
     * The fetching strategy to use for the association, usually defaults to FETCH_LAZY.
     *
     * @var ClassMetadata::FETCH_EAGER|ClassMetadata::FETCH_LAZY
     */
    public int|null $fetch = null;

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

    /** @var array<string, string> */
    public array|null $joinColumnFieldNames = null;

    /** @var list<mixed> */
    public array|null $joinTableColumns = null;

    /** @var class-string */
    public string|null $originalClass = null;

    /** @var string */
    public string|null $originalField = null;

    public bool|null $orphanRemoval = null;

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
    final public function __construct(
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
     *     joinColumns?: mixed[]|null,
     *     joinTable?: mixed[]|null, ...} $mappingArray
     */
    public static function fromMappingArray(array $mappingArray): static
    {
        unset($mappingArray['isOwningSide']);
        $mapping = new static(
            $mappingArray['fieldName'],
            $mappingArray['sourceEntity'],
            $mappingArray['targetEntity'],
        );
        foreach ($mappingArray as $key => $value) {
            if (in_array($key, ['type', 'fieldName', 'sourceEntity', 'targetEntity'])) {
                continue;
            }

            if ($key === 'joinTable') {
                assert($mapping instanceof ManyToManyAssociationMapping);

                if ($value === [] || $value === null) {
                    continue;
                }

                assert($mapping instanceof ManyToManyOwningSideMapping);

                $mapping->joinTable = JoinTableMapping::fromMappingArray($value);

                continue;
            }

            if (property_exists($mapping, $key)) {
                $mapping->$key = $value;
            } else {
                throw new Exception('Unknown property ' . $key . ' on class ' . static::class);
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
        if ($offset === 'isOwningSide') {
            return $this->isOwningSide();
        }

        if ($offset === 'type') {
            return $this->type();
        }

        return $this->$offset;
    }

    /** @psalm-assert AssociationOwningSideMapping $this */
    public function isOwningSide(): bool
    {
        return $this instanceof AssociationOwningSideMapping;
    }

    /** @psalm-assert ToOneAssociationMapping $this */
    public function isToOne(): bool
    {
        return $this instanceof ToOneAssociationMapping;
    }

    /**
     * @psalm-assert-if-true ToOneAssociationMapping $this
     * @psalm-assert-if-true AssociationOwningSideMapping $this
     */
    public function isToOneOwningSide(): bool
    {
        return $this->isToOne() && $this->isOwningSide();
    }

    /** @psalm-assert-if-true ManyToManyOwningSideMapping $this */
    public function isManyToManyOwningSide(): bool
    {
        return $this instanceof ManyToManyOwningSideMapping;
    }

    public function type(): int
    {
        return match (true) {
            $this instanceof OneToOneAssociationMapping => ClassMetadata::ONE_TO_ONE,
            $this instanceof OneToManyAssociationMapping => ClassMetadata::ONE_TO_MANY,
            $this instanceof ManyToOneAssociationMapping => ClassMetadata::MANY_TO_ONE,
            $this instanceof ManyToManyAssociationMapping => ClassMetadata::MANY_TO_MANY,
            default => throw new Exception('Cannot determine type for ' . $this::class),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
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

        $array['isOwningSide'] = $this->isOwningSide();
        $array['type']         = $this->type();

        return $array;
    }
}

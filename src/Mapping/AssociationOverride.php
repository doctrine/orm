<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/** This attribute is used to override association mapping of property for an entity relationship. */
final class AssociationOverride implements MappingAttribute
{
    /**
     * The join column that is being mapped to the persistent attribute.
     *
     * @var array<JoinColumn>|null
     */
    public readonly array|null $joinColumns;

    /**
     * The join column that is being mapped to the persistent attribute.
     *
     * @var array<JoinColumn>|null
     */
    public readonly array|null $inverseJoinColumns;

    /**
     * @param string                       $name               The name of the relationship property whose mapping is being overridden.
     * @param JoinColumn|array<JoinColumn> $joinColumns
     * @param JoinColumn|array<JoinColumn> $inverseJoinColumns
     * @param JoinTable|null               $joinTable          The join table that maps the relationship.
     * @param string|null                  $inversedBy         The name of the association-field on the inverse-side.
     * @phpstan-param 'LAZY'|'EAGER'|'EXTRA_LAZY'|null $fetch
     */
    public function __construct(
        public readonly string $name,
        array|JoinColumn|null $joinColumns = null,
        array|JoinColumn|null $inverseJoinColumns = null,
        public readonly JoinTable|null $joinTable = null,
        public readonly string|null $inversedBy = null,
        public readonly string|null $fetch = null,
    ) {
        if ($joinColumns instanceof JoinColumn) {
            $joinColumns = [$joinColumns];
        }

        if ($inverseJoinColumns instanceof JoinColumn) {
            $inverseJoinColumns = [$inverseJoinColumns];
        }

        $this->joinColumns        = $joinColumns;
        $this->inverseJoinColumns = $inverseJoinColumns;
    }
}

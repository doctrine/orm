<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * This attribute is used to override association mapping of property for an entity relationship.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("ANNOTATION")
 */
final class AssociationOverride implements MappingAttribute
{
    /**
     * The name of the relationship property whose mapping is being overridden.
     *
     * @var string
     * @readonly
     */
    public $name;

    /**
     * The join column that is being mapped to the persistent attribute.
     *
     * @var array<JoinColumn>|null
     * @readonly
     */
    public $joinColumns;

    /**
     * The join column that is being mapped to the persistent attribute.
     *
     * @var array<JoinColumn>|null
     * @readonly
     */
    public $inverseJoinColumns;

    /**
     * The join table that maps the relationship.
     *
     * @var JoinTable|null
     * @readonly
     */
    public $joinTable;

    /**
     * The name of the association-field on the inverse-side.
     *
     * @var string|null
     * @readonly
     */
    public $inversedBy;

    /**
     * The fetching strategy to use for the association.
     *
     * @var string|null
     * @psalm-var 'LAZY'|'EAGER'|'EXTRA_LAZY'|null
     * @readonly
     * @Enum({"LAZY", "EAGER", "EXTRA_LAZY"})
     */
    public $fetch;

    /**
     * @param JoinColumn|array<JoinColumn> $joinColumns
     * @param JoinColumn|array<JoinColumn> $inverseJoinColumns
     * @psalm-param 'LAZY'|'EAGER'|'EXTRA_LAZY'|null $fetch
     */
    public function __construct(
        string $name,
        $joinColumns = null,
        $inverseJoinColumns = null,
        ?JoinTable $joinTable = null,
        ?string $inversedBy = null,
        ?string $fetch = null
    ) {
        if ($joinColumns instanceof JoinColumn) {
            $joinColumns = [$joinColumns];
        }

        if ($inverseJoinColumns instanceof JoinColumn) {
            $inverseJoinColumns = [$inverseJoinColumns];
        }

        $this->name               = $name;
        $this->joinColumns        = $joinColumns;
        $this->inverseJoinColumns = $inverseJoinColumns;
        $this->joinTable          = $joinTable;
        $this->inversedBy         = $inversedBy;
        $this->fetch              = $fetch;
    }
}

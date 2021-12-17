<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * This annotation is used to override association mapping of property for an entity relationship.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("ANNOTATION")
 */
final class AssociationOverride implements Annotation
{
    /**
     * The name of the relationship property whose mapping is being overridden.
     *
     * @var string
     */
    public $name;

    /**
     * The join column that is being mapped to the persistent attribute.
     *
     * @var array<\Doctrine\ORM\Mapping\JoinColumn>|null
     */
    public $joinColumns;

    /**
     * The join column that is being mapped to the persistent attribute.
     *
     * @var array<\Doctrine\ORM\Mapping\JoinColumn>|null
     */
    public $inverseJoinColumns;

    /**
     * The join table that maps the relationship.
     *
     * @var \Doctrine\ORM\Mapping\JoinTable|null
     */
    public $joinTable;

    /**
     * The name of the association-field on the inverse-side.
     *
     * @var ?string
     */
    public $inversedBy;

    /**
     * The fetching strategy to use for the association.
     *
     * @var ?string
     * @Enum({"LAZY", "EAGER", "EXTRA_LAZY"})
     */
    public $fetch;

    /**
     * @param JoinColumn|array<JoinColumn> $joinColumns
     * @param JoinColumn|array<JoinColumn> $inverseJoinColumns
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

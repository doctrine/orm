<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class OneToOne implements Annotation
{
    /** @var class-string|null */
    public $targetEntity;

    /** @var string|null */
    public $mappedBy;

    /** @var string|null */
    public $inversedBy;

    /** @var array<string>|null */
    public $cascade;

    /**
     * The fetching strategy to use for the association.
     *
     * @var string
     * @Enum({"LAZY", "EAGER", "EXTRA_LAZY"})
     */
    public $fetch = 'LAZY';

    /** @var bool */
    public $orphanRemoval = false;

    /**
     * @param class-string|null  $targetEntity
     * @param array<string>|null $cascade
     */
    public function __construct(
        string|null $mappedBy = null,
        string|null $inversedBy = null,
        string|null $targetEntity = null,
        array|null $cascade = null,
        string $fetch = 'LAZY',
        bool $orphanRemoval = false,
    ) {
        $this->mappedBy      = $mappedBy;
        $this->inversedBy    = $inversedBy;
        $this->targetEntity  = $targetEntity;
        $this->cascade       = $cascade;
        $this->fetch         = $fetch;
        $this->orphanRemoval = $orphanRemoval;
    }
}

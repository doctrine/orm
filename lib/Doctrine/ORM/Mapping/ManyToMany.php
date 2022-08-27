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
final class ManyToMany implements Annotation
{
    /** @var class-string|null */
    public $targetEntity;

    /** @var string|null */
    public $mappedBy;

    /** @var string|null */
    public $inversedBy;

    /** @var string[]|null */
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

    /** @var string|null */
    public $indexBy;

    /**
     * @param class-string  $targetEntity
     * @param string[]|null $cascade
     */
    public function __construct(
        string $targetEntity,
        string|null $mappedBy = null,
        string|null $inversedBy = null,
        array|null $cascade = null,
        string $fetch = 'LAZY',
        bool $orphanRemoval = false,
        string|null $indexBy = null,
    ) {
        $this->targetEntity  = $targetEntity;
        $this->mappedBy      = $mappedBy;
        $this->inversedBy    = $inversedBy;
        $this->cascade       = $cascade;
        $this->fetch         = $fetch;
        $this->orphanRemoval = $orphanRemoval;
        $this->indexBy       = $indexBy;
    }
}

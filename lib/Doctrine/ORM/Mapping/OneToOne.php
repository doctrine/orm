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
final class OneToOne implements MappingAttribute
{
    /**
     * @var class-string|null
     * @readonly
     */
    public $targetEntity;

    /**
     * @var string|null
     * @readonly
     */
    public $mappedBy;

    /**
     * @var string|null
     * @readonly
     */
    public $inversedBy;

    /**
     * @var array<string>|null
     * @readonly
     */
    public $cascade;

    /**
     * The fetching strategy to use for the association.
     *
     * @var string
     * @psalm-var 'LAZY'|'EAGER'|'EXTRA_LAZY'
     * @readonly
     * @Enum({"LAZY", "EAGER", "EXTRA_LAZY"})
     */
    public $fetch = 'LAZY';

    /**
     * @var bool
     * @readonly
     */
    public $orphanRemoval = false;

    /**
     * @param class-string|null  $targetEntity
     * @param array<string>|null $cascade
     * @psalm-param 'LAZY'|'EAGER'|'EXTRA_LAZY' $fetch
     */
    public function __construct(
        ?string $mappedBy = null,
        ?string $inversedBy = null,
        ?string $targetEntity = null,
        ?array $cascade = null,
        string $fetch = 'LAZY',
        bool $orphanRemoval = false
    ) {
        $this->mappedBy      = $mappedBy;
        $this->inversedBy    = $inversedBy;
        $this->targetEntity  = $targetEntity;
        $this->cascade       = $cascade;
        $this->fetch         = $fetch;
        $this->orphanRemoval = $orphanRemoval;
    }
}

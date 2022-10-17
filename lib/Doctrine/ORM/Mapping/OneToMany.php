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
final class OneToMany implements Annotation
{
    /**
     * @var string|null
     * @readonly
     */
    public $mappedBy;

    /**
     * @var class-string|null
     * @readonly
     */
    public $targetEntity;

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
     * @var string|null
     * @readonly
     */
    public $indexBy;

    /**
     * @param class-string|null $targetEntity
     * @param string[]|null     $cascade
     * @psalm-param 'LAZY'|'EAGER'|'EXTRA_LAZY' $fetch
     */
    public function __construct(
        string|null $mappedBy = null,
        string|null $targetEntity = null,
        array|null $cascade = null,
        string $fetch = 'LAZY',
        bool $orphanRemoval = false,
        string|null $indexBy = null,
    ) {
        $this->mappedBy      = $mappedBy;
        $this->targetEntity  = $targetEntity;
        $this->cascade       = $cascade;
        $this->fetch         = $fetch;
        $this->orphanRemoval = $orphanRemoval;
        $this->indexBy       = $indexBy;
    }
}

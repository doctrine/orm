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
final class ManyToOne implements Annotation
{
    /**
     * @var class-string|null
     * @readonly
     */
    public $targetEntity;

    /**
     * @var string[]|null
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
     * @var string|null
     * @readonly
     */
    public $inversedBy;

    /**
     * @param class-string|null $targetEntity
     * @param string[]|null     $cascade
     * @psalm-param 'LAZY'|'EAGER'|'EXTRA_LAZY' $fetch
     */
    public function __construct(
        string|null $targetEntity = null,
        array|null $cascade = null,
        string $fetch = 'LAZY',
        string|null $inversedBy = null,
    ) {
        $this->targetEntity = $targetEntity;
        $this->cascade      = $cascade;
        $this->fetch        = $fetch;
        $this->inversedBy   = $inversedBy;
    }
}

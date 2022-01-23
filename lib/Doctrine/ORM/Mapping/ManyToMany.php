<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Deprecations\Deprecation;

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
     * @param class-string|null $targetEntity
     * @param string[]|null     $cascade
     */
    public function __construct(
        ?string $targetEntity = null,
        ?string $mappedBy = null,
        ?string $inversedBy = null,
        ?array $cascade = null,
        string $fetch = 'LAZY',
        bool $orphanRemoval = false,
        ?string $indexBy = null
    ) {
        if ($targetEntity === null) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/issues/8753',
                'Passing no target entity is deprecated.'
            );
        }

        $this->targetEntity  = $targetEntity;
        $this->mappedBy      = $mappedBy;
        $this->inversedBy    = $inversedBy;
        $this->cascade       = $cascade;
        $this->fetch         = $fetch;
        $this->orphanRemoval = $orphanRemoval;
        $this->indexBy       = $indexBy;
    }
}

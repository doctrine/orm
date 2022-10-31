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
final class ManyToMany implements MappingAttribute
{
    /**
     * @param class-string  $targetEntity
     * @param string[]|null $cascade
     * @psalm-param 'LAZY'|'EAGER'|'EXTRA_LAZY' $fetch
     */
    public function __construct(
        public readonly string $targetEntity,
        public readonly string|null $mappedBy = null,
        public readonly string|null $inversedBy = null,
        public readonly array|null $cascade = null,
        public readonly string $fetch = 'LAZY',
        public readonly bool $orphanRemoval = false,
        public readonly string|null $indexBy = null,
    ) {
    }
}

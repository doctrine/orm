<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ManyToOne implements MappingAttribute
{
    /**
     * @param class-string|null $targetEntity
     * @param string[]|null     $cascade
     * @psalm-param 'LAZY'|'EAGER'|'EXTRA_LAZY' $fetch
     */
    public function __construct(
        public readonly string|null $targetEntity = null,
        public readonly array|null $cascade = null,
        public readonly string $fetch = 'LAZY',
        public readonly string|null $inversedBy = null,
    ) {
    }
}

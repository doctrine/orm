<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

/** Caching to an entity or a collection. */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Cache implements MappingAttribute
{
    /** @phpstan-param 'READ_ONLY'|'NONSTRICT_READ_WRITE'|'READ_WRITE' $usage */
    public function __construct(
        public readonly string $usage = 'READ_ONLY',
        public readonly string|null $region = null,
    ) {
    }
}

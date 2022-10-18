<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Caching to an entity or a collection.
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"CLASS","PROPERTY"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Cache implements Annotation
{
    /** @psalm-param 'READ_ONLY'|'NONSTRICT_READ_WRITE'|'READ_WRITE' $usage */
    public function __construct(
        public readonly string $usage = 'READ_ONLY',
        public readonly string|null $region = null,
    ) {
    }
}

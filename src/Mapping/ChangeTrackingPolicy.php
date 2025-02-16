<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ChangeTrackingPolicy implements MappingAttribute
{
    /** @phpstan-param 'DEFERRED_IMPLICIT'|'DEFERRED_EXPLICIT' $value */
    public function __construct(
        public readonly string $value,
    ) {
    }
}

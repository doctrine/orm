<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Id implements MappingAttribute
{
    public function __construct(
        public readonly int $position = 0,
    ) {
    }
}

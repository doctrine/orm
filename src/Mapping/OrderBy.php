<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class OrderBy implements MappingAttribute
{
    /** @param array<string> $value */
    public function __construct(
        public readonly array $value,
    ) {
    }
}

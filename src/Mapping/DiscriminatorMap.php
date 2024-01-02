<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorMap implements MappingAttribute
{
    /** @param array<int|string, string> $value */
    public function __construct(
        public readonly array $value,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class SequenceGenerator implements MappingAttribute
{
    public function __construct(
        public readonly string|null $sequenceName = null,
        public readonly int $allocationSize = 1,
        public readonly int $initialValue = 1,
    ) {
    }
}

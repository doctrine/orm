<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Collections\Order;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class OrderBy implements MappingAttribute
{
    /** @param array<string, string|Order> $value */
    public function __construct(
        public readonly array $value,
    ) {
    }
}

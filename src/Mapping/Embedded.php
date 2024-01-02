<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Embedded implements MappingAttribute
{
    public function __construct(
        public readonly string|null $class = null,
        public readonly string|bool|null $columnPrefix = null,
    ) {
    }
}

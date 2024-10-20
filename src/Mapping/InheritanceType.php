<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class InheritanceType implements MappingAttribute
{
    /** @psalm-param 'NONE'|'JOINED'|'SINGLE_TABLE' $value */
    public function __construct(
        public readonly string $value,
    ) {
    }
}

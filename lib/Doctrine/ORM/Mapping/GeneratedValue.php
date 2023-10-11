<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class GeneratedValue implements MappingAttribute
{
    /** @psalm-param 'AUTO'|'SEQUENCE'|'IDENTITY'|'NONE'|'CUSTOM' $strategy */
    public function __construct(
        public readonly string $strategy = 'AUTO',
    ) {
    }
}

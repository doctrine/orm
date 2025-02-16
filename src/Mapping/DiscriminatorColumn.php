<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use BackedEnum;

#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorColumn implements MappingAttribute
{
    public function __construct(
        public readonly string|null $name = null,
        public readonly string|null $type = null,
        public readonly int|null $length = null,
        public readonly string|null $columnDefinition = null,
        /** @var class-string<BackedEnum>|null */
        public readonly string|null $enumType = null,
        /** @var array<string, mixed> */
        public readonly array $options = [],
    ) {
    }
}

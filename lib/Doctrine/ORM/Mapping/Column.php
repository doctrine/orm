<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use BackedEnum;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Column implements MappingAttribute
{
    /**
     * @param int|null                      $precision The precision for a decimal (exact numeric) column (Applies only for decimal column).
     * @param int|null                      $scale     The scale for a decimal (exact numeric) column (Applies only for decimal column).
     * @param class-string<BackedEnum>|null $enumType
     * @param array<string,mixed>           $options
     * @psalm-param 'NEVER'|'INSERT'|'ALWAYS'|null $generated
     */
    public function __construct(
        public readonly string|null $name = null,
        public readonly string|null $type = null,
        public readonly int|null $length = null,
        public readonly int|null $precision = null,
        public readonly int|null $scale = null,
        public readonly bool $unique = false,
        public readonly bool $nullable = false,
        public readonly bool $insertable = true,
        public readonly bool $updatable = true,
        public readonly string|null $enumType = null,
        public readonly array $options = [],
        public readonly string|null $columnDefinition = null,
        public readonly string|null $generated = null,
    ) {
    }
}

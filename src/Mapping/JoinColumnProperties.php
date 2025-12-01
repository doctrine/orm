<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

trait JoinColumnProperties
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public readonly string|null $name = null,
        public readonly string|null $referencedColumnName = null,
        public readonly bool $deferrable = false,
        public readonly bool $unique = false,
        public readonly bool|null $nullable = null,
        public readonly mixed $onDelete = null,
        public readonly string|null $columnDefinition = null,
        public readonly string|null $fieldName = null,
        public readonly array $options = [],
    ) {
    }
}

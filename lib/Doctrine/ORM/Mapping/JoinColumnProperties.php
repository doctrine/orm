<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

trait JoinColumnProperties
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public readonly string|null $name = null,
        public readonly string $referencedColumnName = 'id',
        public readonly bool $unique = false,
        public readonly bool $nullable = true,
        public readonly mixed $onDelete = null,
        public readonly string|null $columnDefinition = null,
        public readonly string|null $fieldName = null,
        public readonly array $options = [],
    ) {
    }
}

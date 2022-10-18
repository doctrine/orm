<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Table implements Annotation
{
    /**
     * @param array<Index>            $indexes
     * @param array<UniqueConstraint> $uniqueConstraints
     * @param array<string,mixed>     $options
     */
    public function __construct(
        public readonly string|null $name = null,
        public readonly string|null $schema = null,
        public readonly array|null $indexes = null,
        public readonly array|null $uniqueConstraints = null,
        public readonly array $options = [],
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Index implements MappingAttribute
{
    /**
     * @param array<string>|null       $columns
     * @param array<string>|null       $fields
     * @param array<string>|null       $flags
     * @param array<string,mixed>|null $options
     */
    public function __construct(
        public readonly string|null $name = null,
        public readonly array|null $columns = null,
        public readonly array|null $fields = null,
        public readonly array|null $flags = null,
        public readonly array|null $options = null,
    ) {
    }
}

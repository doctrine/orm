<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * This annotation is used to override the mapping of a entity property.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("ANNOTATION")
 */
final class AttributeOverride implements MappingAttribute
{
    public function __construct(
        public string $name,
        public Column $column,
    ) {
    }
}

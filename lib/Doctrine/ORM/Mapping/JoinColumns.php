<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("PROPERTY")
 */
final class JoinColumns implements MappingAttribute
{
    /** @param array<JoinColumn> $value */
    public function __construct(
        public readonly array $value,
    ) {
    }
}

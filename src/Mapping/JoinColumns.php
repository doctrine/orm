<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * @deprecated Using this attribute has no effect, use the `#[JoinColumn]`
 *              attribute instead, it is repeatable.
 */
final class JoinColumns implements MappingAttribute
{
    /** @param array<JoinColumn> $value */
    public function __construct(
        public readonly array $value,
    ) {
    }
}

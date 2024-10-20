<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

/**
 * The EntityListeners attribute specifies the callback listener classes to be used for an entity or mapped superclass.
 * The EntityListeners attribute may be applied to an entity class or mapped superclass.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EntityListeners implements MappingAttribute
{
    /** @param array<string> $value */
    public function __construct(
        public readonly array $value = [],
    ) {
    }
}

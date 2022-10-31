<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Embedded implements MappingAttribute
{
    public function __construct(
        public readonly string|null $class = null,
        public readonly string|bool|null $columnPrefix = null,
    ) {
    }
}

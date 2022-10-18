<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorColumn implements Annotation
{
    public function __construct(
        public readonly string|null $name = null,
        public readonly string|null $type = null,
        public readonly int|null $length = null,
        public readonly string|null $columnDefinition = null,
    ) {
    }
}

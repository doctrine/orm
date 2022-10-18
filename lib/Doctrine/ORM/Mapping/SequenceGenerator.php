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
final class SequenceGenerator implements Annotation
{
    public function __construct(
        public readonly string|null $sequenceName = null,
        public readonly int $allocationSize = 1,
        public readonly int $initialValue = 1,
    ) {
    }
}

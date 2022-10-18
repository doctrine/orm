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
final class ChangeTrackingPolicy implements Annotation
{
    /** @psalm-param 'DEFERRED_IMPLICIT'|'DEFERRED_EXPLICIT'|'NOTIFY' $value */
    public function __construct(
        public readonly string $value,
    ) {
    }
}

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
final class GeneratedValue implements Annotation
{
    /** @psalm-param 'AUTO'|'SEQUENCE'|'IDENTITY'|'NONE'|'CUSTOM' $strategy */
    public function __construct(
        public readonly string $strategy = 'AUTO',
    ) {
    }
}

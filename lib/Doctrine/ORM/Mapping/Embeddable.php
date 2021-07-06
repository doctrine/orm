<?php

namespace Doctrine\ORM\Mapping;

use Attribute;

/**
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Embeddable implements Annotation
{
}

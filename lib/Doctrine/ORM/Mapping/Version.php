<?php

namespace Doctrine\ORM\Mapping;

use Attribute;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Version implements Annotation
{
}

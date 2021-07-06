<?php

namespace Doctrine\ORM\Mapping;

use Attribute;

/**
 * @Annotation
 * @Target("METHOD")
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PreUpdate implements Annotation
{
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class OrderBy implements Annotation
{
    /** @var array<string> */
    public $value;
}

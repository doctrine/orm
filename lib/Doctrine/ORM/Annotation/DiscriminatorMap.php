<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class DiscriminatorMap implements Annotation
{
    /** @var array<string> */
    public $value;
}

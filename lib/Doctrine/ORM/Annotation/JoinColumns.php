<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class JoinColumns implements Annotation
{
    /** @var array<\Doctrine\ORM\Annotation\JoinColumn> */
    public $value;
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class SequenceGenerator implements Annotation
{
    /** @var string */
    public $sequenceName;

    /** @var int */
    public $allocationSize = 1;
}

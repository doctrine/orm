<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class CustomIdGenerator implements Annotation
{
    /** @var string */
    public $class;

    /** @var array */
    public $arguments = [];
}

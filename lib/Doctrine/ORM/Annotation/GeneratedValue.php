<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class GeneratedValue implements Annotation
{
    /**
     * The type of Id generator.
     *
     * @var string
     * @Enum({"AUTO", "SEQUENCE", "TABLE", "IDENTITY", "NONE", "CUSTOM"})
     */
    public $strategy = 'AUTO';
}

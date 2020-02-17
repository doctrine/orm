<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Embedded implements Annotation
{
    /**
     * @Required
     * @var string
     */
    public $class;

    /** @var mixed */
    public $columnPrefix;
}

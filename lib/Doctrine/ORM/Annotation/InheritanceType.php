<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class InheritanceType implements Annotation
{
    /**
     * The inheritance type used by the class and its subclasses.
     *
     * @var string
     * @Enum({"NONE", "JOINED", "SINGLE_TABLE", "TABLE_PER_CLASS"})
     */
    public $value;
}

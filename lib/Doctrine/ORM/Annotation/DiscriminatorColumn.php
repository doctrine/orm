<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class DiscriminatorColumn implements Annotation
{
    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var int */
    public $length;

    /**
     * Field name used in non-object hydration (array/scalar).
     *
     * @var mixed
     */
    public $fieldName;

    /** @var string */
    public $columnDefinition;
}

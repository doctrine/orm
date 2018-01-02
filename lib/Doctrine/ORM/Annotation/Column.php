<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY","ANNOTATION"})
 */
final class Column implements Annotation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var mixed
     */
    public $type = 'string';

    /**
     * The length for a string column (Applied only for string-based column).
     *
     * @var integer
     */
    public $length = 255;

    /**
     * The precision for a decimal (exact numeric) column (Applies only for decimal column).
     *
     * @var integer
     */
    public $precision = 0;

    /**
     * The scale for a decimal (exact numeric) column (Applies only for decimal column).
     *
     * @var integer
     */
    public $scale = 0;

    /**
     * @var boolean
     */
    public $unique = false;

    /**
     * @var boolean
     */
    public $nullable = false;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var string
     */
    public $columnDefinition;
}

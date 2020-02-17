<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
final class Index implements Annotation
{
    /** @var string */
    public $name;

    /** @var array<string> */
    public $columns;

    /** @var bool */
    public $unique = false;

    /** @var array<string> */
    public $flags = [];

    /** @var array */
    public $options = [];
}

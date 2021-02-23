<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Table implements Annotation
{
    /** @var string */
    public $name;

    /** @var string */
    public $schema;

    /** @var array<\Doctrine\ORM\Annotation\Index> */
    public $indexes = [];

    /** @var array<\Doctrine\ORM\Annotation\UniqueConstraint> */
    public $uniqueConstraints = [];

    /** @var array */
    public $options = [];
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Table implements Annotation
{
    /** @var string|null */
    public $name;

    /** @var string|null */
    public $schema;

    /** @var array<\Doctrine\ORM\Mapping\Index>|null */
    public $indexes;

    /** @var array<\Doctrine\ORM\Mapping\UniqueConstraint>|null */
    public $uniqueConstraints;

    /** @var array<string,mixed> */
    public $options = [];

    /**
     * @param array<\Doctrine\ORM\Mapping\Index>            $indexes
     * @param array<\Doctrine\ORM\Mapping\UniqueConstraint> $uniqueConstraints
     * @param array<string,mixed>                           $options
     */
    public function __construct(
        string|null $name = null,
        string|null $schema = null,
        array|null $indexes = null,
        array|null $uniqueConstraints = null,
        array $options = [],
    ) {
        $this->name              = $name;
        $this->schema            = $schema;
        $this->indexes           = $indexes;
        $this->uniqueConstraints = $uniqueConstraints;
        $this->options           = $options;
    }
}

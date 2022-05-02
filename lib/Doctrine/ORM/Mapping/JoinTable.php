<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"PROPERTY","ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class JoinTable implements Annotation
{
    /** @var string|null */
    public $name;

    /** @var string|null */
    public $schema;

    /** @var array<\Doctrine\ORM\Mapping\JoinColumn> */
    public $joinColumns = [];

    /** @var array<\Doctrine\ORM\Mapping\JoinColumn> */
    public $inverseJoinColumns = [];

    /** @var array<string, mixed> */
    public $options = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        ?string $name = null,
        ?string $schema = null,
        $joinColumns = [],
        $inverseJoinColumns = [],
        array $options = []
    ) {
        $this->name               = $name;
        $this->schema             = $schema;
        $this->joinColumns        = $joinColumns instanceof JoinColumn ? [$joinColumns] : $joinColumns;
        $this->inverseJoinColumns = $inverseJoinColumns instanceof JoinColumn
            ? [$inverseJoinColumns]
            : $inverseJoinColumns;
        $this->options            = $options;
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ANNOTATION")
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Index implements Annotation
{
    /** @var string|null */
    public $name;

    /** @var array<string>|null */
    public $columns;

    /** @var array<string>|null */
    public $fields;

    /** @var array<string>|null */
    public $flags;

    /** @var array<string,mixed>|null */
    public $options;

    /**
     * @param array<string>|null $columns
     * @param array<string>|null $fields
     * @param array<string>|null $flags
     * @param array<string>|null $options
     */
    public function __construct(
        ?array $columns = null,
        ?array $fields = null,
        ?string $name = null,
        ?array $flags = null,
        ?array $options = null
    ) {
        $this->columns = $columns;
        $this->fields  = $fields;
        $this->name    = $name;
        $this->flags   = $flags;
        $this->options = $options;
    }
}

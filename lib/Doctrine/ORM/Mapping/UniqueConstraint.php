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
final class UniqueConstraint implements MappingAttribute
{
    /**
     * @var string|null
     * @readonly
     */
    public $name;

    /**
     * @var array<string>|null
     * @readonly
     */
    public $columns;

    /**
     * @var array<string>|null
     * @readonly
     */
    public $fields;

    /**
     * @var array<string,mixed>|null
     * @readonly
     */
    public $options;

    /**
     * @param array<string>|null       $columns
     * @param array<string>|null       $fields
     * @param array<string,mixed>|null $options
     */
    public function __construct(
        ?string $name = null,
        ?array $columns = null,
        ?array $fields = null,
        ?array $options = null
    ) {
        $this->name    = $name;
        $this->columns = $columns;
        $this->fields  = $fields;
        $this->options = $options;
    }
}

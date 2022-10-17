<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * This annotation is used to override the mapping of a entity property.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("ANNOTATION")
 */
final class AttributeOverride implements Annotation
{
    /**
     * The name of the property whose mapping is being overridden.
     *
     * @var string
     * @readonly
     */
    public $name;

    /**
     * The column definition.
     *
     * @var Column
     * @readonly
     */
    public $column;

    public function __construct(string $name, Column $column)
    {
        $this->name   = $name;
        $this->column = $column;
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorColumn implements Annotation
{
    /** @var string|null */
    public $name;

    /** @var string|null */
    public $type;

    /** @var int|null */
    public $length;

    /**
     * Field name used in non-object hydration (array/scalar).
     *
     * @var mixed
     */
    public $fieldName;

    /** @var string */
    public $columnDefinition;

    public function __construct(
        string|null $name = null,
        string|null $type = null,
        int|null $length = null,
        string|null $columnDefinition = null,
    ) {
        $this->name             = $name;
        $this->type             = $type;
        $this->length           = $length;
        $this->columnDefinition = $columnDefinition;
    }
}

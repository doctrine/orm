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

    /**
     * @var string|null
     * @psalm-var 'NEVER'|'INSERT'|'ALWAYS'|null
     * @Enum({"NEVER", "INSERT", "ALWAYS"})
     */
    public $generated;

    public function __construct(
        ?string $name = null,
        ?string $type = null,
        ?int $length = null,
        ?string $columnDefinition = null,
        ?string $generated = null
    ) {
        $this->name             = $name;
        $this->type             = $type;
        $this->length           = $length;
        $this->columnDefinition = $columnDefinition;
        $this->generated        = $generated;
    }
}

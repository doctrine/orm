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
final class Column implements Annotation
{
    /** @var string|null */
    public $name;

    /** @var mixed */
    public $type;

    /** @var int|null */
    public $length;

    /**
     * The precision for a decimal (exact numeric) column (Applies only for decimal column).
     *
     * @var int|null
     */
    public $precision = 0;

    /**
     * The scale for a decimal (exact numeric) column (Applies only for decimal column).
     *
     * @var int|null
     */
    public $scale = 0;

    /** @var bool */
    public $unique = false;

    /** @var bool */
    public $nullable = false;

    /** @var bool */
    public $insertable = true;

    /** @var bool */
    public $updatable = true;

    /** @var class-string<\BackedEnum>|null */
    public $enumType = null;

    /** @var array<string,mixed> */
    public $options = [];

    /** @var string|null */
    public $columnDefinition;

    /**
     * @var string|null
     * @psalm-var 'NEVER'|'INSERT'|'ALWAYS'|null
     * @Enum({"NEVER", "INSERT", "ALWAYS"})
     */
    public $generated;

    /**
     * @param class-string<\BackedEnum>|null $enumType
     * @param array<string,mixed>            $options
     * @psalm-param 'NEVER'|'INSERT'|'ALWAYS'|null $generated
     */
    public function __construct(
        ?string $name = null,
        ?string $type = null,
        ?int $length = null,
        ?int $precision = null,
        ?int $scale = null,
        bool $unique = false,
        bool $nullable = false,
        bool $insertable = true,
        bool $updatable = true,
        ?string $enumType = null,
        array $options = [],
        ?string $columnDefinition = null,
        ?string $generated = null
    ) {
        $this->name             = $name;
        $this->type             = $type;
        $this->length           = $length;
        $this->precision        = $precision;
        $this->scale            = $scale;
        $this->unique           = $unique;
        $this->nullable         = $nullable;
        $this->insertable       = $insertable;
        $this->updatable        = $updatable;
        $this->enumType         = $enumType;
        $this->options          = $options;
        $this->columnDefinition = $columnDefinition;
        $this->generated        = $generated;
    }
}

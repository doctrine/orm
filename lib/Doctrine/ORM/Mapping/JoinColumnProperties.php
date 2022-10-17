<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

trait JoinColumnProperties
{
    /**
     * @var string|null
     * @readonly
     */
    public $name;

    /**
     * @var string
     * @readonly
     */
    public $referencedColumnName = 'id';

    /**
     * @var bool
     * @readonly
     */
    public $unique = false;

    /**
     * @var bool
     * @readonly
     */
    public $nullable = true;

    /**
     * @var mixed
     * @readonly
     */
    public $onDelete;

    /**
     * @var string|null
     * @readonly
     */
    public $columnDefinition;

    /**
     * Field name used in non-object hydration (array/scalar).
     *
     * @var string|null
     * @readonly
     */
    public $fieldName;

    /**
     * @var array<string, mixed>
     * @readonly
     */
    public $options = [];

    /**
     * @param mixed                $onDelete
     * @param array<string, mixed> $options
     */
    public function __construct(
        string|null $name = null,
        string $referencedColumnName = 'id',
        bool $unique = false,
        bool $nullable = true,
        $onDelete = null,
        string|null $columnDefinition = null,
        string|null $fieldName = null,
        array $options = [],
    ) {
        $this->name                 = $name;
        $this->referencedColumnName = $referencedColumnName;
        $this->unique               = $unique;
        $this->nullable             = $nullable;
        $this->onDelete             = $onDelete;
        $this->columnDefinition     = $columnDefinition;
        $this->fieldName            = $fieldName;
        $this->options              = $options;
    }
}

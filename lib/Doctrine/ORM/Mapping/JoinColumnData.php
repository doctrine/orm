<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayAccess;

use function property_exists;

/** @template-implements ArrayAccess<string, mixed> */
final class JoinColumnData implements ArrayAccess
{
    use ArrayAccessImplementation;

    public string|null $name                 = null;
    public bool|null $unique                 = null;
    public bool|null $quoted                 = null;
    public string|null $fieldName            = null;
    public string|null $onDelete             = null;
    public string|null $columnDefinition     = null;
    public bool|null $nullable               = null;
    public string|null $referencedColumnName = null;
    /** @var array<string, mixed> */
    public array|null $options = null;

    public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $mappingArray
     * @psalm-param array{name: string, referencedColumnName: string, ...} $mappingArray
     */
    public static function fromMappingArray(array $mappingArray): self
    {
        $mapping = new self();
        foreach ($mappingArray as $key => $value) {
            if (property_exists($mapping, $key)) {
                $mapping->$key = $value;
            }
        }

        return $mapping;
    }
}

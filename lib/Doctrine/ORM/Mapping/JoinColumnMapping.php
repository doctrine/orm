<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayAccess;

use function property_exists;

/** @template-implements ArrayAccess<string, mixed> */
final class JoinColumnMapping implements ArrayAccess
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

    /** @return list<string> */
    public function __sleep(): array
    {
        $serialized = [];

        foreach (['name', 'fieldName', 'onDelete', 'columnDefinition', 'referencedColumnName', 'options'] as $stringOrArrayKey) {
            if ($this->$stringOrArrayKey !== null) {
                $serialized[] = $stringOrArrayKey;
            }
        }

        foreach (['unique', 'quoted', 'nullable'] as $boolKey) {
            if ($this->$boolKey !== null) {
                $serialized[] = $boolKey;
            }
        }

        return $serialized;
    }
}

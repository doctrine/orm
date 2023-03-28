<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayAccess;

use function array_map;
use function in_array;

/** @template-implements ArrayAccess<string, mixed> */
final class JoinTableMapping implements ArrayAccess
{
    use ArrayAccessImplementation;

    public bool|null $quoted = null;

    /** @var list<JoinColumnMapping> */
    public array $joinColumns = [];

    /** @var list<JoinColumnMapping> */
    public array $inverseJoinColumns = [];

    public string|null $schema = null;

    public string|null $name = null;

    /** @param array{name?: string, quoted?: bool, joinColumns?: mixed[], inverseJoinColumns?: mixed[], schema?: string} $mappingArray */
    public static function fromMappingArray(array $mappingArray): self
    {
        $mapping = new self();

        foreach (['name', 'quoted', 'schema'] as $key) {
            if (isset($mappingArray[$key])) {
                $mapping[$key] = $mappingArray[$key];
            }
        }

        if (isset($mappingArray['joinColumns'])) {
            foreach ($mappingArray['joinColumns'] as $column) {
                $mapping->joinColumns[] = JoinColumnMapping::fromMappingArray($column);
            }
        }

        if (isset($mappingArray['inverseJoinColumns'])) {
            foreach ($mappingArray['inverseJoinColumns'] as $column) {
                $mapping->inverseJoinColumns[] = JoinColumnMapping::fromMappingArray($column);
            }
        }

        return $mapping;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        if (in_array($offset, ['joinColumns', 'inverseJoinColumns'], true)) {
            $joinColumns = [];
            foreach ($value as $column) {
                $joinColumns[] = JoinColumnMapping::fromMappingArray($column);
            }

            $value = $joinColumns;
        }

        $this->$offset = $value;
    }

    /** @return mixed[] */
    public function toArray(): array
    {
        $array = (array) $this;

        $toArray                     = static fn (JoinColumnMapping $column): array => (array) $column;
        $array['joinColumns']        = array_map($toArray, $array['joinColumns']);
        $array['inverseJoinColumns'] = array_map($toArray, $array['inverseJoinColumns']);

        return $array;
    }

    /** @return list<string> */
    public function __sleep(): array
    {
        $serialized = [];

        foreach (['joinColumns', 'inverseJoinColumns', 'name', 'schema'] as $stringOrArrayKey) {
            if ($this->$stringOrArrayKey !== null) {
                $serialized[] = $stringOrArrayKey;
            }
        }

        foreach (['quoted'] as $boolKey) {
            if ($this->$boolKey) {
                $serialized[] = $boolKey;
            }
        }

        return $serialized;
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayAccess;

use function array_map;
use function in_array;

/** @template-implements ArrayAccess<string, mixed> */
final class JoinTableMapping implements ArrayAccess
{
    public bool|null $quoted = null;

    /** @var list<JoinColumnData> */
    public array|null $joinColumns = null;

    /** @var list<JoinColumnData> */
    public array|null $inverseJoinColumns = null;

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
                $mapping->joinColumns[] = JoinColumnData::fromMappingArray($column);
            }
        }

        if (isset($mappingArray['inverseJoinColumns'])) {
            foreach ($mappingArray['inverseJoinColumns'] as $column) {
                $mapping->inverseJoinColumns[] = JoinColumnData::fromMappingArray($column);
            }
        }

        return $mapping;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->$offset;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        if (in_array($offset, ['joinColumns', 'inverseJoinColumns'], true)) {
            $joinColumns = [];
            foreach ($value as $column) {
                $joinColumns[] = JoinColumnData::fromMappingArray($column);
            }

            $value = $joinColumns;
        }

        $this->$offset = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        $this->$offset = null;
    }

    /** @return mixed[] */
    public function toArray(): array
    {
        $array = (array) $this;

        if (isset($array['joinColumns'])) {
            $array['joinColumns'] = array_map(static fn (JoinColumnData $column): array => (array) $column, $array['joinColumns']);
        }

        if (isset($array['inverseJoinColumns'])) {
            $array['inverseJoinColumns'] = array_map(static fn (JoinColumnData $column): array => (array) $column, $array['inverseJoinColumns']);
        }

        return $array;
    }
}

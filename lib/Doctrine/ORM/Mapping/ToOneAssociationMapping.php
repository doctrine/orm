<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class ToOneAssociationMapping extends AssociationMapping
{
    /** @var array<string, string> */
    public array|null $sourceToTargetKeyColumns = null;

    /** @var array<string, string> */
    public array|null $targetToSourceKeyColumns = null;

    /** @var list<JoinColumnData>|null */
    public array|null $joinColumns = null;

    /** @psalm-param array{joinColumns?: mixed[], ...} $mapping */
    public static function fromMappingArray(array $mapping): static
    {
        $joinColumns = $mapping['joinColumns'] ?? [];

        if (isset($mapping['joinColumns'])) {
            unset($mapping['joinColumns']);
        }

        $instance = parent::fromMappingArray($mapping);

        foreach ($joinColumns as $column) {
            $instance->joinColumns[] = JoinColumnData::fromMappingArray($column);
        }

        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === 'joinColumns') {
            $joinColumns = [];
            foreach ($value as $column) {
                $joinColumns[] = JoinColumnData::fromMappingArray($column);
            }

            $this->joinColumns = $joinColumns;

            return;
        }

        parent::offsetSet($offset, $value);
    }

    /** @return mixed[] */
    public function toArray(): array
    {
        $array = parent::toArray();

        if ($array['joinColumns'] !== null) {
            $joinColumns = [];
            foreach ($array['joinColumns'] as $column) {
                $joinColumns[] = (array) $column;
            }

            $array['joinColumns'] = $joinColumns;
        }

        return $array;
    }
}

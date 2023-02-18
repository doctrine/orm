<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use function assert;

abstract class ToOneAssociationMapping extends AssociationMapping
{
    /** @var array<string, string> */
    public array|null $sourceToTargetKeyColumns = null;

    /** @var array<string, string> */
    public array|null $targetToSourceKeyColumns = null;

    /**
     * @param array<string, mixed> $mappingArray
     * @psalm-param array{
     *     fieldName: string,
     *     sourceEntity: class-string,
     *     targetEntity: class-string,
     *     joinColumns?: mixed[]|null,
     *     isOwningSide: bool, ...} $mappingArray
     */
    public static function fromMappingArray(array $mappingArray): OneToOneAssociationMapping|ManyToOneAssociationMapping
    {
        $joinColumns = $mappingArray['joinColumns'] ?? [];

        if (isset($mappingArray['joinColumns'])) {
            unset($mappingArray['joinColumns']);
        }

        $instance = parent::fromMappingArray($mappingArray);

        foreach ($joinColumns as $column) {
            assert($instance->isToOneOwningSide());
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
            assert($this->isToOneOwningSide());
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

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\DBAL\Types\Type;

/**
 * Hydrator that produces flat, rectangular results of scalar data.
 * The created result is almost the same as a regular SQL result set, except
 * that column names are mapped to field names and data type conversions take place.
 */
class ScalarHydrator extends AbstractHydrator
{
    /** @var array<string, array{string|int, Type|null}|null> */
    private array $scalarColumns = [];

    protected function cleanup(): void
    {
        parent::cleanup();

        $this->scalarColumns = [];
    }

    /** {@inheritDoc} */
    protected function gatherScalarRowData(array &$data): array
    {
        $rowData = [];

        foreach ($data as $key => $value) {
            $column = $this->scalarColumns[$key] ?? $this->prepareScalarColumn($key);
            if ($column === null) {
                continue;
            }

            [$fieldName, $type]  = $column;
            $rowData[$fieldName] = $type === null ? $value : $type->convertToPHPValue($value, $this->platform);
        }

        return $rowData;
    }

    /** @return array{string|int, Type|null}|null */
    private function prepareScalarColumn(string $key): array|null
    {
        $info = $this->hydrateColumnInfo($key);

        // Keep only the information needed by scalar hydration.
        unset($this->cache[$key]);

        if ($info === null) {
            return null;
        }

        if (isset($info['isScalar'])) {
            // Preserve the legacy behavior: scalar mappings are not type converted.
            return $this->scalarColumns[$key] = [$info['fieldName'], null];
        }

        return $this->scalarColumns[$key] = [$info['dqlAlias'] . '_' . $info['fieldName'], $info['type']];
    }

    /**
     * {@inheritDoc}
     */
    protected function hydrateAllData(): array
    {
        $result = [];

        while ($data = $this->statement()->fetchAssociative()) {
            $this->hydrateRowData($data, $result);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function hydrateRowData(array $row, array &$result): void
    {
        $result[] = $this->gatherScalarRowData($row);
    }
}

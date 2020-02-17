<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\DBAL\FetchMode;

/**
 * Hydrator that produces flat, rectangular results of scalar data.
 * The created result is almost the same as a regular SQL result set, except
 * that column names are mapped to field names and data type conversions take place.
 */
class ScalarHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $result = [];

        while ($data = $this->stmt->fetch(FetchMode::ASSOCIATIVE)) {
            $this->hydrateRowData($data, $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrateRowData(array $data, array &$result)
    {
        $result[] = $this->gatherScalarRowData($data);
    }
}

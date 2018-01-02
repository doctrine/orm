<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

/**
 * Hydrator that produces flat, rectangular results of scalar data.
 * The created result is almost the same as a regular SQL result set, except
 * that column names are mapped to field names and data type conversions take place.
 *
 * @since  2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ScalarHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $result = [];

        while ($data = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
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

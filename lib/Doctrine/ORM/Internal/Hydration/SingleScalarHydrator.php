<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\Exception\NonUniqueResult;
use Doctrine\ORM\Exception\NoResult;
use function array_shift;
use function count;
use function key;

/**
 * Hydrator that hydrates a single scalar value from the result set.
 */
class SingleScalarHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $data    = $this->stmt->fetchAll(FetchMode::ASSOCIATIVE);
        $numRows = count($data);

        if ($numRows === 0) {
            throw new NoResult();
        }

        if ($numRows > 1) {
            throw new NonUniqueResult('The query returned multiple rows. Change the query or use a different result function like getScalarResult().');
        }

        if (count($data[key($data)]) > 1) {
            throw new NonUniqueResult('The query returned a row containing multiple columns. Change the query or use a different result function like getScalarResult().');
        }

        $result = $this->gatherScalarRowData($data[key($data)]);

        return array_shift($result);
    }
}

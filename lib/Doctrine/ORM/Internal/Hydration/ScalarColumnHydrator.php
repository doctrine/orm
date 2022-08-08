<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\Exception\MultipleSelectorsFoundException;

use function array_column;
use function array_combine;
use function count;

/**
 * Hydrator that produces one-dimensional array.
 */
final class ScalarColumnHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     *
     * @throws MultipleSelectorsFoundException
     * @throws Exception
     */
    protected function hydrateAllData(): array
    {
        if (count($this->resultSetMapping()->fieldMappings) > 1) {
            throw MultipleSelectorsFoundException::create($this->resultSetMapping()->fieldMappings);
        }

        $result = $this->statement()->fetchAllNumeric();

        $resultColumn = array_column($result, 0);

        if (isset($this->resultSetMapping()->indexByMap['scalars'])) {
            return array_combine($resultColumn, $resultColumn);
        }

        return $resultColumn;
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\Exception\MultipleSelectorsFoundException;

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

        $result = [];

        while ($row = $this->statement()->fetchOne()) {
            $result[] = $row;
        }

        return $result;
    }
}

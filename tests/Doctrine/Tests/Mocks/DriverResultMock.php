<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use BadMethodCallException;
use Doctrine\DBAL\Driver\Result;

use function array_values;
use function count;
use function current;
use function next;
use function reset;

class DriverResultMock implements Result
{
    /** @var list<array<string, mixed>> */
    private $resultSet;

    /**
     * Creates a new mock statement that will serve the provided fake result set to clients.
     *
     * @param list<array<string, mixed>> $resultSet The faked SQL result set.
     */
    public function __construct(array $resultSet = [])
    {
        $this->resultSet = $resultSet;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNumeric()
    {
        $row = $this->fetchAssociative();

        return $row === false ? false : array_values($row);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssociative()
    {
        $current = current($this->resultSet);
        next($this->resultSet);

        return $current;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne()
    {
        $row = $this->fetchNumeric();

        return $row ? $row[0] : false;
    }

    public function fetchAllNumeric(): array
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function fetchAllAssociative(): array
    {
        $resultSet = $this->resultSet;
        reset($resultSet);

        return $resultSet;
    }

    public function fetchFirstColumn(): array
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function rowCount(): int
    {
        return 0;
    }

    public function columnCount(): int
    {
        $resultSet = $this->resultSet;

        return count(reset($resultSet) ?: []);
    }

    public function free(): void
    {
    }
}

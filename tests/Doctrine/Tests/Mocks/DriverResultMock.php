<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use ArrayIterator;
use BadMethodCallException;
use Doctrine\DBAL\Driver\Result;
use PDO;
use Traversable;

use function array_values;
use function count;
use function current;
use function next;
use function reset;

class DriverResultMock implements Result, ResultStatement
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
        $values = [];
        while (($row = $this->fetchNumeric()) !== false) {
            $values[] = $row;
        }

        return $values;
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

    public function closeCursor(): bool
    {
        $this->free();

        return true;
    }

    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($fetchMode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        return $this->fetchAssociative();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null): array
    {
        return $this->fetchAllAssociative();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        return $this->fetchOne();
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->fetchAllAssociative());
    }
}

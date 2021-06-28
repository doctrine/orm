<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use BadMethodCallException;
use Doctrine\DBAL\Result;
use Traversable;

use function count;
use function current;
use function next;
use function reset;

class HydratorMockResult implements Result
{
    /** @var list<array<string, mixed>> */
    private $resultSet;

    /**
     * Creates a new mock statement that will serve the provided fake result set to clients.
     *
     * @param list<array<string, mixed>> $resultSet The faked SQL result set.
     */
    public function __construct(array $resultSet)
    {
        $this->resultSet = $resultSet;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNumeric()
    {
        throw new BadMethodCallException('Not implemented');
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
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociative(): array
    {
        $resultSet = $this->resultSet;
        reset($resultSet);

        return $resultSet;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociativeIndexed(): array
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllKeyValue(): array
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllNumeric(): array
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirstColumn(): array
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function iterateKeyValue(): Traversable
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function iterateColumn(): Traversable
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function columnCount(): int
    {
        $resultSet = $this->resultSet;

        return count(reset($resultSet) ?: []);
    }

    public function iterateNumeric(): Traversable
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function iterateAssociative(): Traversable
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function iterateAssociativeIndexed(): Traversable
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function rowCount(): int
    {
        return count($this->resultSet);
    }

    public function free(): void
    {
    }
}

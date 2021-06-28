<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use BadMethodCallException;
use Doctrine\DBAL\Driver\Result as DriverResult;
use Doctrine\DBAL\Result;
use Traversable;

class ResultMock implements Result
{
    /** @var DriverResult */
    private $result;

    /**
     * Creates a new mock statement that will serve the provided fake result set to clients.
     *
     * @param list<array<string, mixed>> $resultSet The faked SQL result set.
     */
    public function __construct(array $resultSet)
    {
        $this->result = new DriverResultMock($resultSet);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNumeric()
    {
        return $this->result->fetchNumeric();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssociative()
    {
        return $this->result->fetchAssociative();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne()
    {
        return $this->result->fetchOne();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->result->fetchAllAssociative();
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
        return $this->result->fetchAllNumeric();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->result->fetchFirstColumn();
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
        return $this->result->columnCount();
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
        return $this->result->rowCount();
    }

    public function free(): void
    {
    }
}

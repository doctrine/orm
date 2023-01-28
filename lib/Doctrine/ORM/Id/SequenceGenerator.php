<?php

declare(strict_types=1);

namespace Doctrine\ORM\Id;

use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\ORM\EntityManagerInterface;
use Serializable;

use function serialize;
use function unserialize;

/**
 * Represents an ID generator that uses a database sequence.
 */
class SequenceGenerator extends AbstractIdGenerator implements Serializable
{
    /**
     * The allocation size of the sequence.
     *
     * @var int
     */
    private $allocationSize;

    /**
     * The name of the sequence.
     *
     * @var string
     */
    private $sequenceName;

    /** @var int */
    private $nextValue = 0;

    /** @var int|null */
    private $maxValue = null;

    /**
     * Initializes a new sequence generator.
     *
     * @param string $sequenceName   The name of the sequence.
     * @param int    $allocationSize The allocation size of the sequence.
     */
    public function __construct($sequenceName, $allocationSize)
    {
        $this->sequenceName   = $sequenceName;
        $this->allocationSize = $allocationSize;
    }

    /**
     * {@inheritDoc}
     */
    public function generateId(EntityManagerInterface $em, $entity)
    {
        if ($this->maxValue === null || $this->nextValue === $this->maxValue) {
            // Allocate new values
            $connection = $em->getConnection();
            $sql        = $connection->getDatabasePlatform()->getSequenceNextValSQL($this->sequenceName);

            if ($connection instanceof PrimaryReadReplicaConnection) {
                $connection->ensureConnectedToPrimary();
            }

            $this->nextValue = (int) $connection->fetchOne($sql);
            $this->maxValue  = $this->nextValue + $this->allocationSize;
        }

        return $this->nextValue++;
    }

    /**
     * Gets the maximum value of the currently allocated bag of values.
     *
     * @return int|null
     */
    public function getCurrentMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * Gets the next value that will be returned by generate().
     *
     * @return int
     */
    public function getNextValue()
    {
        return $this->nextValue;
    }

    /**
     * @return string
     *
     * @final
     */
    public function serialize()
    {
        return serialize($this->__serialize());
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        return [
            'allocationSize' => $this->allocationSize,
            'sequenceName' => $this->sequenceName,
        ];
    }

    /**
     * @param string $serialized
     *
     * @return void
     *
     * @final
     */
    public function unserialize($serialized)
    {
        $this->__unserialize(unserialize($serialized));
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        $this->sequenceName   = $data['sequenceName'];
        $this->allocationSize = $data['allocationSize'];
    }
}

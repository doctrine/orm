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
    private $_allocationSize;

    /**
     * The name of the sequence.
     *
     * @var string
     */
    private $_sequenceName;

    /** @var int */
    private $_nextValue = 0;

    /** @var int|null */
    private $_maxValue = null;

    /**
     * Initializes a new sequence generator.
     *
     * @param string $sequenceName   The name of the sequence.
     * @param int    $allocationSize The allocation size of the sequence.
     */
    public function __construct($sequenceName, $allocationSize)
    {
        $this->_sequenceName   = $sequenceName;
        $this->_allocationSize = $allocationSize;
    }

    /**
     * {@inheritDoc}
     */
    public function generateId(EntityManagerInterface $em, $entity)
    {
        if ($this->_maxValue === null || $this->_nextValue === $this->_maxValue) {
            // Allocate new values
            $connection = $em->getConnection();
            $sql        = $connection->getDatabasePlatform()->getSequenceNextValSQL($this->_sequenceName);

            if ($connection instanceof PrimaryReadReplicaConnection) {
                $connection->ensureConnectedToPrimary();
            }

            $this->_nextValue = (int) $connection->executeQuery($sql)->fetchOne();
            $this->_maxValue  = $this->_nextValue + $this->_allocationSize;
        }

        return $this->_nextValue++;
    }

    /**
     * Gets the maximum value of the currently allocated bag of values.
     *
     * @return int|null
     */
    public function getCurrentMaxValue()
    {
        return $this->_maxValue;
    }

    /**
     * Gets the next value that will be returned by generate().
     *
     * @return int
     */
    public function getNextValue()
    {
        return $this->_nextValue;
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

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'allocationSize' => $this->_allocationSize,
            'sequenceName' => $this->_sequenceName,
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

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->_sequenceName   = $data['sequenceName'];
        $this->_allocationSize = $data['allocationSize'];
    }
}

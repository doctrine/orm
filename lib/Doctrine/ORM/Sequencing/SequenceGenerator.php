<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing;

use Doctrine\ORM\EntityManagerInterface;
use Serializable;
use function serialize;
use function unserialize;

/**
 * Represents an ID generator that uses a database sequence.
 */
class SequenceGenerator implements Generator, Serializable
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
    private $maxValue;

    public function __construct(string $sequenceName, int $allocationSize)
    {
        $this->sequenceName   = $sequenceName;
        $this->allocationSize = $allocationSize;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(EntityManagerInterface $em, ?object $entity)
    {
        if ($this->maxValue === null || $this->nextValue === $this->maxValue) {
            // Allocate new values
            $conn = $em->getConnection();
            $sql  = $conn->getDatabasePlatform()->getSequenceNextValSQL($this->sequenceName);

            // Using `query` to force usage of the master server in MasterSlaveConnection
            $this->nextValue = (int) $conn->query($sql)->fetchColumn();
            $this->maxValue  = $this->nextValue + $this->allocationSize;
        }

        return $this->nextValue++;
    }

    /**
     * Gets the maximum value of the currently allocated bag of values.
     */
    public function getCurrentMaxValue() : ?int
    {
        return $this->maxValue;
    }

    /**
     * Gets the next value that will be returned by generate().
     */
    public function getNextValue() : int
    {
        return $this->nextValue;
    }

    public function serialize() : string
    {
        return serialize(
            [
                'allocationSize' => $this->allocationSize,
                'sequenceName'   => $this->sequenceName,
            ]
        );
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized) : void
    {
        $array = unserialize($serialized);

        $this->sequenceName   = $array['sequenceName'];
        $this->allocationSize = $array['allocationSize'];
    }

    /**
     * {@inheritdoc}
     */
    public function isPostInsertGenerator() : bool
    {
        return false;
    }
}

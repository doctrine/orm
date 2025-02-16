<?php

declare(strict_types=1);

namespace Doctrine\ORM\Id;

use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Serializable;

use function serialize;
use function unserialize;

/**
 * Represents an ID generator that uses a database sequence.
 */
class SequenceGenerator extends AbstractIdGenerator implements Serializable
{
    private int $nextValue     = 0;
    private int|null $maxValue = null;

    /**
     * Initializes a new sequence generator.
     *
     * @param string $sequenceName   The name of the sequence.
     * @param int    $allocationSize The allocation size of the sequence.
     */
    public function __construct(
        private string $sequenceName,
        private int $allocationSize,
    ) {
    }

    public function generateId(EntityManagerInterface $em, object|null $entity): int
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
     */
    public function getCurrentMaxValue(): int|null
    {
        return $this->maxValue;
    }

    /**
     * Gets the next value that will be returned by generate().
     */
    public function getNextValue(): int
    {
        return $this->nextValue;
    }

    /** @deprecated without replacement. */
    final public function serialize(): string
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/11468',
            '%s() is deprecated, use __serialize() instead. %s won\'t implement the Serializable interface anymore in ORM 4.',
            __METHOD__,
            self::class,
        );

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

    /** @deprecated without replacement. */
    final public function unserialize(string $serialized): void
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/11468',
            '%s() is deprecated, use __unserialize() instead. %s won\'t implement the Serializable interface anymore in ORM 4.',
            __METHOD__,
            self::class,
        );

        $this->__unserialize(unserialize($serialized));
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        $this->sequenceName   = $data['sequenceName'];
        $this->allocationSize = $data['allocationSize'];
    }
}

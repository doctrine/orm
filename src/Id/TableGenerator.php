<?php

declare(strict_types=1);

namespace Doctrine\ORM\Id;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Id generator that uses a single-row database table and a hi/lo algorithm.
 *
 * @deprecated no replacement planned
 */
class TableGenerator extends AbstractIdGenerator
{
    /** @var string */
    private $tableName;

    /** @var string */
    private $sequenceName;

    /** @var int */
    private $allocationSize;

    /** @var int|null */
    private $nextValue;

    /** @var int|null */
    private $maxValue;

    /**
     * @param string $tableName
     * @param string $sequenceName
     * @param int    $allocationSize
     */
    public function __construct($tableName, $sequenceName = 'default', $allocationSize = 10)
    {
        $this->tableName      = $tableName;
        $this->sequenceName   = $sequenceName;
        $this->allocationSize = $allocationSize;
    }

    /**
     * {@inheritDoc}
     */
    public function generateId(
        EntityManagerInterface $em,
        $entity
    ) {
        if ($this->maxValue === null || $this->nextValue === $this->maxValue) {
            // Allocate new values
            $conn = $em->getConnection();

            if ($conn->getTransactionNestingLevel() === 0) {
                // use select for update
                $sql          = $conn->getDatabasePlatform()->getTableHiLoCurrentValSql($this->tableName, $this->sequenceName);
                $currentLevel = $conn->fetchOne($sql);

                if ($currentLevel !== null) {
                    $this->nextValue = $currentLevel;
                    $this->maxValue  = $this->nextValue + $this->allocationSize;

                    $updateSql = $conn->getDatabasePlatform()->getTableHiLoUpdateNextValSql(
                        $this->tableName,
                        $this->sequenceName,
                        $this->allocationSize
                    );

                    if ($conn->executeStatement($updateSql, [1 => $currentLevel, 2 => $currentLevel + 1]) !== 1) {
                        // no affected rows, concurrency issue, throw exception
                    }
                } else {
                    // no current level returned, TableGenerator seems to be broken, throw exception
                }
            } else {
                // only table locks help here, implement this or throw exception?
                // or do we want to work with table locks exclusively?
            }
        }

        return $this->nextValue++;
    }
}

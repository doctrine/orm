<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Class JoinTableMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
final class JoinTableMetadata extends TableMetadata
{
    /** @var array<JoinColumnMetadata> */
    protected $joinColumns = [];

    /** @var array<JoinColumnMetadata> */
    protected $inverseJoinColumns = [];

    /**
     * @return bool
     */
    public function hasColumns() : bool
    {
        return $this->joinColumns || $this->inverseJoinColumns;
    }

    /**
     * @return array<JoinColumnMetadata>
     */
    public function getJoinColumns() : array
    {
        return $this->joinColumns;
    }

    /**
     * @param JoinColumnMetadata $joinColumn
     */
    public function addJoinColumn(JoinColumnMetadata $joinColumn) : void
    {
        $this->joinColumns[] = $joinColumn;
    }

    /**
     * @return array<JoinColumnMetadata>
     */
    public function getInverseJoinColumns() : array
    {
        return $this->inverseJoinColumns;
    }

    /**
     * @param JoinColumnMetadata $joinColumn
     */
    public function addInverseJoinColumn(JoinColumnMetadata $joinColumn) : void
    {
        $this->inverseJoinColumns[] = $joinColumn;
    }

    public function __clone()
    {
        foreach ($this->joinColumns as $index => $joinColumn) {
            $this->joinColumns[$index] = clone $joinColumn;
        }

        foreach ($this->inverseJoinColumns as $index => $inverseJoinColumn) {
            $this->inverseJoinColumns[$index] = clone $inverseJoinColumn;
        }
    }
}

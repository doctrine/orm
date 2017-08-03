<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class JoinTableMetadata extends TableMetadata
{
    /** @var array<JoinColumnMetadata> */
    protected $joinColumns = [];

    /** @var array<JoinColumnMetadata> */
    protected $inverseJoinColumns = [];

    /**
     * @return bool
     */
    public function hasColumns()
    {
        return $this->joinColumns || $this->inverseJoinColumns;
    }

    /**
     * @return array<JoinColumnMetadata>
     */
    public function getJoinColumns()
    {
        return $this->joinColumns;
    }

    /**
     * @param JoinColumnMetadata $joinColumn
     */
    public function addJoinColumn(JoinColumnMetadata $joinColumn)
    {
        $this->joinColumns[] = $joinColumn;
    }

    /**
     * @return array<JoinColumnMetadata>
     */
    public function getInverseJoinColumns()
    {
        return $this->inverseJoinColumns;
    }

    /**
     * @param JoinColumnMetadata $joinColumn
     */
    public function addInverseJoinColumn(JoinColumnMetadata $joinColumn)
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

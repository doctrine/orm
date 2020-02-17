<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class JoinTableMetadata extends TableMetadata
{
    /** @var JoinColumnMetadata[] */
    protected $joinColumns = [];

    /** @var JoinColumnMetadata[] */
    protected $inverseJoinColumns = [];

    public function hasColumns() : bool
    {
        return $this->joinColumns || $this->inverseJoinColumns;
    }

    /**
     * @return JoinColumnMetadata[]
     */
    public function getJoinColumns() : array
    {
        return $this->joinColumns;
    }

    public function addJoinColumn(JoinColumnMetadata $joinColumn) : void
    {
        $this->joinColumns[] = $joinColumn;
    }

    /**
     * @return JoinColumnMetadata[]
     */
    public function getInverseJoinColumns() : array
    {
        return $this->inverseJoinColumns;
    }

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

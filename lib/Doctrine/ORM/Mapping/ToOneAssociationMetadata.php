<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class ToOneAssociationMetadata extends AssociationMetadata
{
    /** @var JoinColumnMetadata[] */
    private $joinColumns = [];

    /**
     * @param JoinColumnMetadata[] $joinColumns
     */
    public function setJoinColumns(array $joinColumns) : void
    {
        $this->joinColumns = $joinColumns;
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

    public function __clone()
    {
        parent::__clone();

        foreach ($this->joinColumns as $index => $joinColumn) {
            $this->joinColumns[$index] = clone $joinColumn;
        }
    }
}

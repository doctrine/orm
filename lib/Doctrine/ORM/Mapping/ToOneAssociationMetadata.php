<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class ToOneAssociationMetadata extends AssociationMetadata
{
    /**
     * @var array<JoinColumnMetadata>
     */
    private $joinColumns = [];

    /**
     * @param array<JoinColumnMetadata> $joinColumns
     */
    public function setJoinColumns(array $joinColumns)
    {
        $this->joinColumns = $joinColumns;
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

    public function __clone()
    {
        parent::__clone();

        foreach ($this->joinColumns as $index => $joinColumn) {
            $this->joinColumns[$index] = clone $joinColumn;
        }
    }
}

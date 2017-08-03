<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class ManyToManyAssociationMetadata extends ToManyAssociationMetadata
{
    /** @var null|JoinTableMetadata */
    private $joinTable;

    /**
     * @param null|JoinTableMetadata $joinTable
     */
    public function setJoinTable(JoinTableMetadata $joinTable = null)
    {
        $this->joinTable = $joinTable;
    }

    /**
     * @return JoinTableMetadata|null
     */
    public function getJoinTable()
    {
        return $this->joinTable;
    }

    public function __clone()
    {
        parent::__clone();

        if ($this->joinTable) {
            $this->joinTable = clone $this->joinTable;
        }
    }
}

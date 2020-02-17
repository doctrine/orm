<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class ManyToManyAssociationMetadata extends ToManyAssociationMetadata
{
    /** @var JoinTableMetadata|null */
    private $joinTable;

    public function setJoinTable(?JoinTableMetadata $joinTable = null) : void
    {
        $this->joinTable = $joinTable;
    }

    public function getJoinTable() : ?JoinTableMetadata
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

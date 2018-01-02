<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Class ManyToManyAssociationMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ManyToManyAssociationMetadata extends ToManyAssociationMetadata
{
    /** @var null|JoinTableMetadata */
    private $joinTable;

    /**
     * @param null|JoinTableMetadata $joinTable
     */
    public function setJoinTable(JoinTableMetadata $joinTable = null) : void
    {
        $this->joinTable = $joinTable;
    }

    /**
     * @return JoinTableMetadata|null
     */
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

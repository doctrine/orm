<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Class ToOneAssociationMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ToOneAssociationMetadata extends AssociationMetadata
{
    /**
     * @var array<JoinColumnMetadata>
     */
    private $joinColumns = [];

    /**
     * @param array<JoinColumnMetadata> $joinColumns
     */
    public function setJoinColumns(array $joinColumns) : void
    {
        $this->joinColumns = $joinColumns;
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

    public function __clone()
    {
        parent::__clone();

        foreach ($this->joinColumns as $index => $joinColumn) {
            $this->joinColumns[$index] = clone $joinColumn;
        }
    }
}

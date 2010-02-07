<?php

namespace Doctrine\DBAL\Schema\Visitor;

use Doctrine\DBAL\Platforms\AbstractPlatform,
    Doctrine\DBAL\Schema\Table,
    Doctrine\DBAL\Schema\Schema,
    Doctrine\DBAL\Schema\Column,
    Doctrine\DBAL\Schema\ForeignKeyConstraint,
    Doctrine\DBAL\Schema\Constraint,
    Doctrine\DBAL\Schema\Sequence,
    Doctrine\DBAL\Schema\Index;

class FixSchema implements Visitor
{
    /**
     * @var bool
     */
    private $_addExplicitIndexForForeignKey = null;

    public function __construct($addExplicitIndexForForeignKey)
    {
        $this->_addExplicitIndexForForeignKey = $addExplicitIndexForForeignKey;
    }

    /**
     * @param Schema $schema
     */
    public function acceptSchema(Schema $schema)
    {
        
    }

    /**
     * @param Table $table
     */
    public function acceptTable(Table $table)
    {

    }

    /**
     * @param Column $column
     */
    public function acceptColumn(Table $table, Column $column)
    {
        
    }

    /**
     * @param Table $localTable
     * @param ForeignKeyConstraint $fkConstraint
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        if ($this->_addExplicitIndexForForeignKey) {
            $columns = $fkConstraint->getColumns();
            if ($localTable->columnsAreIndexed($columns)) {
                return;
            }

            $localTable->addIndex($columns);
        }
    }

    /**
     * @param Table $table
     * @param Index $index
     */
    public function acceptIndex(Table $table, Index $index)
    {

    }

    /**
     * @param Sequence $sequence
     */
    public function acceptSequence(Sequence $sequence)
    {

    }
}
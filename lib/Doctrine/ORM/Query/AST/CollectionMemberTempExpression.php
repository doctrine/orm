<?php

namespace Doctrine\ORM\Query\AST;

class CollectionMemberTempExpression extends Node
{
    /**
     * @var Literal
     */
    public $tableNameExpr;

    /**
     * @var PathExpression
     */
    public $entityExpr;

    /**
     * @var bool
     */
    public $not;

    public function __construct(PathExpression $entityExpr, Literal $tableName)
    {
        $this->entityExpr = $entityExpr;
        $this->tableNameExpr = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkCollectionMemberTempExpression($this);
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * CollectionMemberExpression ::= EntityExpression ["NOT"] "MEMBER" ["OF"] CollectionValuedPathExpression
 *
 * 
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class CollectionMemberExpression extends Node
{
    public $entityExpression;

    /**
     * @var PathExpression
     */
    public $collectionValuedPathExpression;

    /**
     * @var bool
     */
    public $not;

    /**
     * @param mixed          $entityExpr
     * @param PathExpression $collValuedPathExpr
     */
    public function __construct($entityExpr, $collValuedPathExpr)
    {
        $this->entityExpression = $entityExpr;
        $this->collectionValuedPathExpression = $collValuedPathExpr;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkCollectionMemberExpression($this);
    }
}

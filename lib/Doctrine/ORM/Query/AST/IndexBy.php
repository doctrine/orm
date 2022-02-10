<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * IndexBy ::= "INDEX" "BY" SingleValuedPathExpression
 *
 * @link    www.doctrine-project.org
 */
class IndexBy extends Node
{
    /** @var PathExpression */
    public $singleValuedPathExpression = null;

    public function __construct(PathExpression $singleValuedPathExpression)
    {
        $this->singleValuedPathExpression = $singleValuedPathExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkIndexBy($this);
    }
}

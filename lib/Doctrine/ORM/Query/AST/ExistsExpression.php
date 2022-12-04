<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ExistsExpression ::= ["NOT"] "EXISTS" "(" Subselect ")"
 *
 * @link    www.doctrine-project.org
 */
class ExistsExpression extends Node
{
    /** @var bool */
    public $not;

    /** @var Subselect */
    public $subselect;

    /** @param Subselect $subselect */
    public function __construct($subselect, bool $not = false)
    {
        $this->subselect = $subselect;
        $this->not       = $not;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkExistsExpression($this);
    }
}

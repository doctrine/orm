<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * JoinVariableDeclaration ::= Join [IndexBy]
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link    www.doctrine-project.org
 * @since   2.5
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class JoinVariableDeclaration extends Node
{
    /**
     * @var Join 
     */
    public $join;

    /**
     * @var IndexBy|null 
     */
    public $indexBy;

    /**
     * Constructor.
     * 
     * @param Join         $join
     * @param IndexBy|null $indexBy
     */
    public function __construct($join, $indexBy)
    {
        $this->join    = $join;
        $this->indexBy = $indexBy;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkJoinVariableDeclaration($this);
    }
}
